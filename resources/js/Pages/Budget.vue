<script setup>
/**
 * Pages/Budget.vue — Department Breakdown
 * ─────────────────────────────────────────────────────────────
 * Thin orchestrator — passes data to sub-components.
 *
 * Sub-components (all in Pages/Budget/):
 *   YearSummaryCards.vue — 3 KPI cards per year
 *   RankingTab.vue       — expandable ranking table
 *
 * Tabs:
 *   ranking  → expandable budget ranking
 *   fundmix  → stacked fund bar + detail table
 *   yoy      → 3-year grouped bar + change table
 *
 * Note: Top Movers tab removed — use Dept Breakdown for analysis.
 */
import { ref, computed } from 'vue'
import AppLayout   from '@/Layouts/AppLayout.vue'
import SectionCard from '@/Components/SectionCard.vue'
import { Bar, Line } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement,
  LineElement, PointElement, Title, Tooltip, Legend
} from 'chart.js'

// ── Budget sub-components ─────────────────────────────────────
import YearSummaryCards from './Budget/YearSummaryCards.vue'
import RankingTab       from './Budget/RankingTab.vue'

// ── Composables ───────────────────────────────────────────────
import { useFormatters }                from '@/composables/useFormatters'
import { useExpandRows }                from '@/composables/useExpandRows'
import { useFundMixChart, useYoyChart } from '@/composables/useChartConfigs'
import { COLORS }                       from '@/constants/wfp'

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend)

// ── Props (from BudgetController.php) ────────────────────────
const props = defineProps({
  allByYear:  Object,  // { 2024: [...depts+children], 2025: [...], 2026: [...] }
  yearTotals: Object,  // { 2024: { total, f101, f164, f161, f163 }, ... }
  yoyRows:    Array,   // matched parent rows across all 3 years
  gainers:    Array,
  losers:     Array,
  fundMix:    Array,
})

// ── UI state ──────────────────────────────────────────────────
const activeTab = ref('ranking')
const search    = ref('')

// ── Tabs config — add/remove tabs here only ───────────────────
const TABS = [
  { id: 'ranking', label: 'Budget Ranking' },
  { id: 'fundmix', label: 'Fund Mix'       },
  { id: 'yoy',     label: '3-Year Comparison' },
]

// ── Composables ───────────────────────────────────────────────
const { php, phpM, pct } = useFormatters()
const { toggleExpand, isExpanded, expandAll: expandAllRows, collapseAll, expanded: expandedRows } = useExpandRows()

// ── Expand helpers ────────────────────────────────────────────
const expandAll = () => expandAllRows(
  parentRows.value.filter(r => r._hasChildren).map(r => r.department)
)
const isRowExpanded = isExpanded

// ── Year summary ──────────────────────────────────────────────
const yt = computed(() => props.yearTotals ?? {})

// ── Charts ────────────────────────────────────────────────────
const fundMixRows = computed(() => props.fundMix ?? [])
const yoyRowsRef  = computed(() => props.yoyRows ?? [])
const { fundMixData, fundMixOpts } = useFundMixChart(fundMixRows)
const { yoyData: yoyChartData, yoyOpts: yoyChartOpts } = useYoyChart(yoyRowsRef)

// Trend line (inline — uses yearTotals shape unique to this page)
const trendData = computed(() => ({
  labels: ['FY 2024', 'FY 2025', 'FY 2026'],
  datasets: [
    { label: 'Total Budget',        data: [2024,2025,2026].map(y => yt.value[y]?.total ?? 0), borderColor: COLORS.navy,  backgroundColor: COLORS.navyLight, fill: true,  tension: 0.3, pointRadius: 6, borderWidth: 2.5 },
    { label: 'Fund 101 (GAA)',       data: [2024,2025,2026].map(y => yt.value[y]?.f101  ?? 0), borderColor: COLORS.gold,  backgroundColor: 'transparent',    fill: false, tension: 0.3, pointRadius: 4, borderWidth: 2,   borderDash: [5,4] },
    { label: 'Fund 164 (Fiduciary)', data: [2024,2025,2026].map(y => yt.value[y]?.f164  ?? 0), borderColor: COLORS.green, backgroundColor: 'transparent',    fill: false, tension: 0.3, pointRadius: 4, borderWidth: 2,   borderDash: [3,3] },
  ],
}))
const trendOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend:  { labels: { color: COLORS.gray, font: { size: 11 }, boxWidth: 12, padding: 16 } },
    tooltip: { callbacks: { label: c => '  ₱' + Number(c.raw).toLocaleString('en-PH') } },
  },
  scales: {
    x: { ticks: { color: COLORS.grayLight }, grid: { color: COLORS.gridLine } },
    y: { ticks: { color: COLORS.grayLight, callback: v => '₱' + (v/1e6).toFixed(0) + 'M' }, grid: { color: COLORS.gridLine } },
  },
}

// ── Tree data: parent rows with combined totals ───────────────
const allRows2025 = computed(() => props.allByYear?.[2025] ?? [])
const allRows2026 = computed(() => props.allByYear?.[2026] ?? [])

// Children lookup: PARENT_NAME → children[] (from nested children arrays)
const childrenByParent = computed(() => {
  const map = {}
  const addFrom = (yearRows) => {
    ;(yearRows ?? []).forEach(r => {
      if (r.children?.length) {
        const pk = r.department.toUpperCase()
        if (!map[pk]) map[pk] = {}
        r.children.forEach(c => { map[pk][c.department.toUpperCase()] = c })
      }
    })
  }
  addFrom(allRows2025.value)
  addFrom(allRows2026.value)
  return Object.fromEntries(Object.entries(map).map(([k, v]) => [k, Object.values(v)]))
})

// Enrich a parent yoyRow with combined totals (own + children).
// Each year's children come from allByYear[yr] directly — this ensures
// 2025 children and 2026 children are NEVER mixed together.
const enrichParent = (r) => {
  // Find this dept's row in each year's allByYear data
  // allByYear[yr] already has budget_total = own + children combined
  // and own_budget = just the parent's own allocation
  const findInYear = (yr) => {
    const name = r.department.toUpperCase()
    return (props.allByYear?.[yr] ?? []).find(d =>
      d.department.toUpperCase() === name
    ) ?? null
  }

  const row2024 = findInYear(2024)
  const row2025 = findInYear(2025)
  const row2026 = findInYear(2026)

  // combined = budget_total from allByYear (already includes children for that year)
  const c2024 = row2024?.budget_total ?? r.budget_2024 ?? null
  const c2025 = row2025?.budget_total ?? r.budget_2025 ?? null
  const c2026 = row2026?.budget_total ?? r.budget_2026 ?? null

  // own = just the parent row's own budget (no children)
  const own2024 = row2024?.own_budget ?? r.budget_2024 ?? 0
  const own2025 = row2025?.own_budget ?? r.budget_2025 ?? 0
  const own2026 = row2026?.own_budget ?? r.budget_2026 ?? 0

  const chg = (c2025 && c2026)
    ? Math.round((c2026 - c2025) / c2025 * 1000) / 10
    : r.chg_25_26

  // Children for display — use the most recent year that has them
  const children = row2026?.children ?? row2025?.children ?? []

  return {
    ...r,
    _isChild: false,
    _hasChildren: children.length > 0,
    _childCount:  children.length,
    _own_2024: own2024, _own_2025: own2025, _own_2026: own2026,
    _combined_2024: c2024, _combined_2025: c2025, _combined_2026: c2026,
    budget_2024: c2024, budget_2025: c2025, budget_2026: c2026,
    chg_25_26: chg,
  }
}

const parentRows = computed(() => {
  const q = search.value.toLowerCase()
  return (props.yoyRows ?? [])
    .filter(r => !q || r.department.toLowerCase().includes(q))
    .map(r => enrichParent(r))
})

// Flat tree: parents interleaved with their children
const filteredTree = computed(() => {
  const q = search.value.toLowerCase()
  const result = []
  parentRows.value.forEach(parent => {
    result.push(parent)
    if (parent._hasChildren) {
      // Get children from the most recent year available for this parent
      // Each year's allByYear entry has its own children array — no cross-year mixing
      const parentName = parent.department.toUpperCase()
      const row2026 = (props.allByYear?.[2026] ?? []).find(d => d.department.toUpperCase() === parentName)
      const row2025 = (props.allByYear?.[2025] ?? []).find(d => d.department.toUpperCase() === parentName)

      // Build a unified child list: prefer 2026, fall back to 2025
      // Show each child's budget per year from its own year's data
      const allChildren = new Map()

      // Add 2025 children first
      ;(row2025?.children ?? []).forEach(c => {
        allChildren.set(c.department.toUpperCase(), {
          department: c.department, sheet_code: c.sheet_code ?? '',
          budget_2025: c.budget_total ?? null, budget_2026: null, budget_2024: null,
          f101_2026: 0, f164_2026: 0,
        })
      })

      // Merge / add 2026 children
      ;(row2026?.children ?? []).forEach(c => {
        const key = c.department.toUpperCase()
        const existing = allChildren.get(key) ?? {
          department: c.department, sheet_code: c.sheet_code ?? '',
          budget_2025: null, budget_2024: null,
        }
        allChildren.set(key, {
          ...existing,
          department: c.department, // prefer 2026 name
          budget_2026: c.budget_total ?? null,
          f101_2026: c.budget_fund_101 ?? 0,
          f164_2026: c.budget_fund_164 ?? 0,
        })
      })

      allChildren.forEach(child => {
        const chg = (child.budget_2025 && child.budget_2026)
          ? Math.round((child.budget_2026 - child.budget_2025) / child.budget_2025 * 1000) / 10
          : null
        if (!q || child.department.toLowerCase().includes(q)) {
          result.push({
            ...child, chg_25_26: chg,
            _isChild: true, _parentName: parent.department, _hasChildren: false, _childCount: 0,
          })
        }
      })
    }
  })
  return result
})

// ── Fund mix detail rows ──────────────────────────────────────
const trendColor = v => v == null ? 'text-gray-300' : v > 0 ? 'text-emerald-600' : v < 0 ? 'text-red-500' : 'text-gray-400'
const trendBg    = v => v == null ? '' : v > 0 ? 'bg-emerald-50 border-emerald-100' : v < 0 ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-100'
</script>

<template>
  <AppLayout>
    <template #breadcrumb>Department Breakdown</template>
    <template #title>Department Breakdown</template>
    <template #subtitle>Budget analysis across all departments · FY 2024–2026</template>

    <div class="space-y-5">

      <!-- Year summary KPI cards -->
      <YearSummaryCards :yearTotals="yt" />

      <!-- Trend line -->
      <SectionCard title="Budget Trend" subtitle="Total budget · Fund 101 · Fund 164 across FY 2024–2026">
        <div class="h-[180px]"><Line :data="trendData" :options="trendOpts" /></div>
      </SectionCard>

      <!-- Tab navigation -->
      <div class="flex items-center gap-1 bg-gray-100 rounded-2xl p-1 w-fit">
        <button v-for="tab in TABS" :key="tab.id" @click="activeTab = tab.id"
          :class="['px-4 py-2 rounded-xl text-[12px] font-bold transition-all',
            activeTab === tab.id ? 'bg-white text-[#0D2137] shadow-sm' : 'text-gray-400 hover:text-gray-600']">
          {{ tab.label }}
        </button>
      </div>

      <!-- ── Tab: BUDGET RANKING ─────────────────────────────── -->
      <RankingTab
        v-if="activeTab === 'ranking'"
        :filteredTree="filteredTree"
        :expandedRows="expandedRows"
        :parentRows="parentRows"
        @toggle="toggleExpand"
        @expand-all="expandAll"
        @collapse-all="collapseAll"
        @update:search="search = $event" />

      <!-- ── Tab: FUND MIX ──────────────────────────────────── -->
      <div v-if="activeTab === 'fundmix'" class="space-y-4">
        <SectionCard title="Fund Mix per Department" subtitle="FY 2026 · Fund 101 / 164 / 161 / 163 stacked · top 15 departments">
          <div class="h-[380px]"><Bar :data="fundMixData" :options="fundMixOpts" /></div>
        </SectionCard>

        <SectionCard title="Fund Breakdown Detail" subtitle="FY 2026 · all fund clusters per department" :noPad="true">
          <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
              <thead>
                <tr class="border-b-2 border-gray-100">
                  <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department</th>
                  <th v-for="col in [{l:'Total'},{l:'Fund 101'},{l:'Fund 164'},{l:'Fund 161'},{l:'Fund 163'},{l:'Mix'}]" :key="col.l"
                    class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">{{ col.l }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in yoyRows.filter(x => x.budget_2026)" :key="r.sheet_code"
                  class="border-b border-gray-50 hover:bg-[#0D2137]/[0.02] transition-colors">
                  <td class="px-5 py-3 font-semibold text-[#0D2137] text-[12px]">{{ r.department }}</td>
                  <td class="px-4 py-3 text-right font-mono font-bold text-[#0D2137]">{{ phpM(r.budget_2026) }}</td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">{{ r.f101_2026 > 0 ? phpM(r.f101_2026) : '—' }}</td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">{{ r.f164_2026 > 0 ? phpM(r.f164_2026) : '—' }}</td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">{{ r.f161_2026 > 0 ? phpM(r.f161_2026) : '—' }}</td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">{{ r.f163_2026 > 0 ? phpM(r.f163_2026) : '—' }}</td>
                  <td class="px-4 py-3">
                    <div class="flex h-2 rounded-full overflow-hidden gap-px">
                      <div v-if="r.f101_2026 > 0" class="bg-[#0D2137]" :style="{flex: r.f101_2026}" />
                      <div v-if="r.f164_2026 > 0" class="bg-[#C9A84C]" :style="{flex: r.f164_2026}" />
                      <div v-if="r.f161_2026 > 0" class="bg-[#1E8449]" :style="{flex: r.f161_2026}" />
                      <div v-if="r.f163_2026 > 0" class="bg-[#2E86C1]" :style="{flex: r.f163_2026}" />
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </SectionCard>
      </div>

      <!-- ── Tab: 3-YEAR COMPARISON ─────────────────────────── -->
      <div v-if="activeTab === 'yoy'" class="space-y-4">
        <SectionCard title="3-Year Budget Comparison" subtitle="Top 15 departments by FY 2026 budget">
          <div class="h-[380px]"><Bar :data="yoyChartData" :options="yoyChartOpts" /></div>
        </SectionCard>

        <SectionCard title="Year-over-Year Changes" subtitle="Budget movement per department" :noPad="true">
          <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
              <thead>
                <tr class="border-b-2 border-gray-100">
                  <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">FY 2024</th>
                  <th class="text-center px-2 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">24→25</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">FY 2025</th>
                  <th class="text-center px-2 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">25→26</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">FY 2026</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in yoyRows" :key="r.sheet_code"
                  class="border-b border-gray-50 hover:bg-[#0D2137]/[0.02] transition-colors">
                  <td class="px-5 py-3 font-semibold text-[#0D2137] text-[12px]">{{ r.department }}</td>
                  <td class="px-4 py-3 text-right font-mono text-gray-400 text-[11px]">{{ r.budget_2024 ? phpM(r.budget_2024) : '—' }}</td>
                  <td class="px-2 py-3 text-center">
                    <span v-if="r.chg_24_25 != null" :class="['text-[10px] font-bold px-1.5 py-0.5 rounded-full border', trendBg(r.chg_24_25), trendColor(r.chg_24_25)]">{{ pct(r.chg_24_25) }}</span>
                    <span v-else class="text-gray-200 text-[10px]">—</span>
                  </td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500 text-[11px]">{{ r.budget_2025 ? phpM(r.budget_2025) : '—' }}</td>
                  <td class="px-2 py-3 text-center">
                    <span v-if="r.chg_25_26 != null" :class="['text-[10px] font-bold px-1.5 py-0.5 rounded-full border', trendBg(r.chg_25_26), trendColor(r.chg_25_26)]">{{ pct(r.chg_25_26) }}</span>
                    <span v-else class="text-gray-200 text-[10px]">—</span>
                  </td>
                  <td class="px-4 py-3 text-right font-mono font-bold text-[#0D2137] text-[12px]">{{ r.budget_2026 ? phpM(r.budget_2026) : '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </SectionCard>
      </div>

    </div>
  </AppLayout>
</template>