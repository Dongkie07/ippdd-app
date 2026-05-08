<script setup>
/**
 * Pages/Budget.vue — Department Breakdown
 * ─────────────────────────────────────────────────────────────────────
 * All year references are now DYNAMIC — no hardcoded 2024/2025/2026.
 * Years are driven entirely by props.years from BudgetController.php.
 */
import { ref, computed } from 'vue'
import AppLayout   from '@/Layouts/AppLayout.vue'
import SectionCard from '@/Components/SectionCard.vue'
import { Bar, Line } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement,
  LineElement, PointElement, Title, Tooltip, Legend
} from 'chart.js'

// ── Budget sub-components ───────────────────────────────────────────
import YearSummaryCards from './Budget/YearSummaryCards.vue'
import RankingTab       from './Budget/RankingTab.vue'

// ── Composables ─────────────────────────────────────────────────────
import { useFormatters }                from '@/composables/useFormatters'
import { useExpandRows }                from '@/composables/useExpandRows'
import { useFundMixChart, useYoyChart } from '@/composables/useChartConfigs'
import { COLORS }                       from '@/constants/wfp'

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend)

// ── Props (from BudgetController.php) ──────────────────────────────
const props = defineProps({
  years:      Array,   // dynamic list from DB e.g. [2024, 2025, 2026, 2027]
  allByYear:  Object,  // { 2024: [...], 2025: [...], ... }
  yearTotals: Object,  // { 2024: { total, f101, ... }, ... }
  yoyRows:    Array,   // matched parent rows across all years
  gainers:    Array,
  losers:     Array,
  fundMix:    Array,
})

// ── UI state ────────────────────────────────────────────────────────
const activeTab = ref('ranking')
const search    = ref('')

const TABS = [
  { id: 'ranking', label: 'Budget Ranking'  },
  { id: 'fundmix', label: 'Fund Mix'        },
  { id: 'yoy',     label: 'Year Comparison' },
]

// ── Composables ─────────────────────────────────────────────────────
const { php, phpM, pct } = useFormatters()
const {
  toggleExpand, isExpanded,
  expandAll: expandAllRows,
  collapseAll,
  expanded: expandedRows,
} = useExpandRows()

// ── Dynamic year helpers ────────────────────────────────────────────
const yearsRef      = computed(() => props.years ?? [])
const latestYearRef = computed(() => yearsRef.value.at(-1) ?? null)
const yt            = computed(() => props.yearTotals ?? {})

// ── Expand helpers ──────────────────────────────────────────────────
const expandAll = () =>
  expandAllRows(parentRows.value.filter(r => r._hasChildren).map(r => r.department))

// ── Charts ──────────────────────────────────────────────────────────
const fundMixRows = computed(() => props.yoyRows ?? [])
const yoyRowsRef  = computed(() => props.yoyRows  ?? [])

const { fundMixData, fundMixOpts } = useFundMixChart(fundMixRows, latestYearRef)
const { yoyData: yoyChartData, yoyOpts: yoyChartOpts } = useYoyChart(yoyRowsRef, yearsRef)

// Trend line — fully dynamic using yearTotals keys
const trendData = computed(() => {
  const yrs = yearsRef.value
  return {
    labels: yrs.map(y => `FY ${y}`),
    datasets: [
      {
        label:           'Total Budget',
        data:            yrs.map(y => yt.value[y]?.total ?? 0),
        borderColor:     COLORS.navy,
        backgroundColor: COLORS.navyLight,
        fill:            true, tension: 0.3, pointRadius: 6, borderWidth: 2.5,
      },
      {
        label:           'Fund 101 (GAA)',
        data:            yrs.map(y => yt.value[y]?.f101 ?? 0),
        borderColor:     COLORS.gold,
        backgroundColor: 'transparent',
        fill:            false, tension: 0.3, pointRadius: 4, borderWidth: 2, borderDash: [5, 4],
      },
      {
        label:           'Fund 164 (Fiduciary)',
        data:            yrs.map(y => yt.value[y]?.f164 ?? 0),
        borderColor:     COLORS.green,
        backgroundColor: 'transparent',
        fill:            false, tension: 0.3, pointRadius: 4, borderWidth: 2, borderDash: [3, 3],
      },
    ],
  }
})
const trendOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend:  { labels: { color: COLORS.gray, font: { size: 11 }, boxWidth: 12, padding: 16 } },
    tooltip: { callbacks: { label: c => '  ₱' + Number(c.raw).toLocaleString('en-PH') } },
  },
  scales: {
    x: { ticks: { color: '#9CA3AF' }, grid: { color: '#F3F4F6' } },
    y: { ticks: { color: '#9CA3AF', callback: v => '₱' + (v / 1e6).toFixed(0) + 'M' }, grid: { color: '#F3F4F6' } },
  },
}

// ── Tree data: enrich yoyRows dynamically ───────────────────────────
// enrichParent uses props.years instead of hardcoded 2024/2025/2026
const enrichParent = (r) => {
  const yrs = yearsRef.value

  // For each year, find this dept in allByYear[yr]
  const findInYear = (yr) => {
    const name = r.department.toUpperCase()
    return (props.allByYear?.[yr] ?? []).find(d =>
      d.department.toUpperCase() === name
    ) ?? null
  }

  // Build per-year budget values dynamically
  const yearBudgets = {}
  yrs.forEach(yr => {
    const row = findInYear(yr)
    yearBudgets[`budget_${yr}`]   = row?.budget_total ?? r[`budget_${yr}`] ?? null
    yearBudgets[`own_${yr}`]      = row?.own_budget   ?? r[`budget_${yr}`] ?? 0
  })

  // Latest two years for change calculation
  const latest = yrs.at(-1)
  const prev   = yrs.at(-2) ?? null
  const bLatest = yearBudgets[`budget_${latest}`]
  const bPrev   = prev ? yearBudgets[`budget_${prev}`] : null

  const chg = (bPrev && bLatest)
    ? Math.round((bLatest - bPrev) / bPrev * 1000) / 10
    : null

  // Children from the most recent year that has them
  let children = []
  for (let i = yrs.length - 1; i >= 0; i--) {
    const row = findInYear(yrs[i])
    if (row?.children?.length) { children = row.children; break }
  }

  return {
    ...r,
    ...yearBudgets,
    _isChild:     false,
    _hasChildren: children.length > 0,
    _childCount:  children.length,
    [`chg_${String(prev).slice(2)}_${String(latest).slice(2)}`]: chg,
  }
}

const parentRows = computed(() => {
  const q = search.value.toLowerCase()
  return (props.yoyRows ?? [])
    .filter(r => !q || r.department.toLowerCase().includes(q))
    .map(r => enrichParent(r))
})

// Flat tree: parents interleaved with their children (fully dynamic)
const filteredTree = computed(() => {
  const q    = search.value.toLowerCase()
  const yrs  = yearsRef.value
  const latest = yrs.at(-1)
  const prev   = yrs.at(-2) ?? null
  const result = []

  parentRows.value.forEach(parent => {
    result.push(parent)

    if (!parent._hasChildren) return

    const parentName = parent.department.toUpperCase()

    // Build unified child list from all years, prefer latest year's data
    const allChildren = new Map()

    yrs.forEach(yr => {
      const yearRow = (props.allByYear?.[yr] ?? [])
        .find(d => d.department.toUpperCase() === parentName)
      ;(yearRow?.children ?? []).forEach(c => {
        const key = c.department.toUpperCase()
        const existing = allChildren.get(key) ?? {
          department: c.department,
          sheet_code: c.sheet_code ?? '',
        }
        // Set budget for this year
        existing[`budget_${yr}`]   = c.budget_total    ?? null
        existing[`f101_${yr}`]     = c.budget_fund_101 ?? 0
        existing[`f164_${yr}`]     = c.budget_fund_164 ?? 0
        existing[`f161_${yr}`]     = c.budget_fund_161 ?? 0
        existing[`f163_${yr}`]     = c.budget_fund_163 ?? 0
        // Prefer the latest year's name
        if (yr === latest) existing.department = c.department
        allChildren.set(key, existing)
      })
    })

    allChildren.forEach(child => {
      const bL = child[`budget_${latest}`]
      const bP = prev ? child[`budget_${prev}`] : null
      const chg = (bP && bL)
        ? Math.round((bL - bP) / bP * 1000) / 10
        : null

      if (!q || child.department.toLowerCase().includes(q)) {
        result.push({
          ...child,
          [`chg_${String(prev).slice(2)}_${String(latest).slice(2)}`]: chg,
          _isChild:     true,
          _parentName:  parent.department,
          _hasChildren: false,
          _childCount:  0,
        })
      }
    })
  })

  return result
})

// ── Best year helper (for fund mix table)
const bestYearFor = (r) => {
  const keys = Object.keys(r)
    .filter(k => k.startsWith('budget_') && (r[k] ?? 0) > 0)
    .map(k => parseInt(k.replace('budget_', '')))
    .sort((a, b) => b - a)
  return keys[0] ?? latestYearRef.value
}

// ── Trend color helpers ─────────────────────────────────────────────
const trendColor = v => v == null
  ? 'text-gray-300'
  : v > 0 ? 'text-emerald-600' : v < 0 ? 'text-red-500' : 'text-gray-400'
const trendBg = v => v == null
  ? ''
  : v > 0 ? 'bg-emerald-50 border-emerald-100' : v < 0 ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-100'
</script>

<template>
  <AppLayout>
    <template #breadcrumb>Department Breakdown</template>
    <template #title>Department Breakdown</template>
    <template #subtitle>
      Budget analysis across all departments · FY {{ yearsRef[0] }}–{{ latestYearRef }}
    </template>

    <div class="space-y-5">

      <!-- Year summary KPI cards -->
      <YearSummaryCards :yearTotals="yt" :years="yearsRef" />

      <!-- Trend line -->
      <SectionCard
        title="Budget Trend"
        :subtitle="`Total budget · Fund 101 · Fund 164 across FY ${yearsRef[0]}–${latestYearRef}`">
        <div class="h-[180px]"><Line :data="trendData" :options="trendOpts" /></div>
      </SectionCard>

      <!-- Tab navigation -->
      <div class="flex items-center gap-1 bg-gray-100 rounded-2xl p-1 w-fit">
        <button v-for="tab in TABS" :key="tab.id" @click="activeTab = tab.id"
          :class="['px-4 py-2 rounded-xl text-[12px] font-bold transition-all',
            activeTab === tab.id
              ? 'bg-white text-[#0D2137] shadow-sm'
              : 'text-gray-400 hover:text-gray-600']">
          {{ tab.label }}
        </button>
      </div>

      <!-- ── Tab: BUDGET RANKING ──────────────────────────────────── -->
      <RankingTab
        v-if="activeTab === 'ranking'"
        :filteredTree="filteredTree"
        :expandedRows="expandedRows"
        :parentRows="parentRows"
        :years="yearsRef"
        @toggle="toggleExpand"
        @expand-all="expandAll"
        @collapse-all="collapseAll"
        @update:search="search = $event"
      />

      <!-- ── Tab: FUND MIX ───────────────────────────────────────── -->
      <div v-if="activeTab === 'fundmix'" class="space-y-4">
        <SectionCard
          title="Fund Mix per Department"
          :subtitle="`FY ${latestYearRef} · Fund 101 / 164 / 161 / 163 stacked · top 15 departments`">
          <div class="h-[380px]"><Bar :data="fundMixData" :options="fundMixOpts" /></div>
        </SectionCard>

        <SectionCard
          title="Fund Breakdown Detail"
          :subtitle="`FY ${latestYearRef} · all fund clusters per department`"
          :noPad="true">
          <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
              <thead>
                <tr class="border-b-2 border-gray-100">
                  <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department</th>
                  <th v-for="col in ['Total','Fund 101','Fund 164','Fund 161','Fund 163','Mix']" :key="col"
                    class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
                    {{ col }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in (yoyRows ?? []).filter(x => Object.keys(x).some(k => k.startsWith('budget_') && x[k] > 0)).sort((a,b) => Math.max(...Object.keys(b).filter(k=>k.startsWith('budget_')).map(k=>b[k]??0)) - Math.max(...Object.keys(a).filter(k=>k.startsWith('budget_')).map(k=>a[k]??0)))"
                  :key="r.sheet_code"
                  class="border-b border-gray-50 hover:bg-[#0D2137]/[0.02] transition-colors">
                  <td class="px-5 py-3 font-semibold text-[#0D2137] text-[12px]">
                    {{ r.department }}
                    <span class="text-[9px] font-mono text-gray-300 ml-1">FY {{ bestYearFor(r) }}</span>
                  </td>
                  <td class="px-4 py-3 text-right font-mono font-bold text-[#0D2137]">
                    {{ phpM(r[`budget_${bestYearFor(r)}`]) }}
                  </td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">
                    {{ (r[`f101_${bestYearFor(r)}`] ?? 0) > 0 ? phpM(r[`f101_${bestYearFor(r)}`]) : '—' }}
                  </td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">
                    {{ (r[`f164_${bestYearFor(r)}`] ?? 0) > 0 ? phpM(r[`f164_${bestYearFor(r)}`]) : '—' }}
                  </td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">
                    {{ (r[`f161_${bestYearFor(r)}`] ?? 0) > 0 ? phpM(r[`f161_${bestYearFor(r)}`]) : '—' }}
                  </td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">
                    {{ (r[`f163_${bestYearFor(r)}`] ?? 0) > 0 ? phpM(r[`f163_${bestYearFor(r)}`]) : '—' }}
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex h-2 rounded-full overflow-hidden gap-px">
                      <div v-if="(r[`f101_${bestYearFor(r)}`] ?? 0) > 0" class="bg-[#0D2137]"
                        :style="{ flex: r[`f101_${bestYearFor(r)}`] }" />
                      <div v-if="(r[`f164_${bestYearFor(r)}`] ?? 0) > 0" class="bg-[#C9A84C]"
                        :style="{ flex: r[`f164_${bestYearFor(r)}`] }" />
                      <div v-if="(r[`f161_${bestYearFor(r)}`] ?? 0) > 0" class="bg-[#1E8449]"
                        :style="{ flex: r[`f161_${bestYearFor(r)}`] }" />
                      <div v-if="(r[`f163_${bestYearFor(r)}`] ?? 0) > 0" class="bg-[#2E86C1]"
                        :style="{ flex: r[`f163_${bestYearFor(r)}`] }" />
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </SectionCard>
      </div>

      <!-- ── Tab: YEAR COMPARISON ────────────────────────────────── -->
      <div v-if="activeTab === 'yoy'" class="space-y-4">
        <SectionCard
          title="Multi-Year Budget Comparison"
          :subtitle="`Top 15 departments by FY ${latestYearRef} budget`">
          <div class="h-[380px]"><Bar :data="yoyChartData" :options="yoyChartOpts" /></div>
        </SectionCard>

        <SectionCard
          :title="`Year-over-Year Changes — FY ${yearsRef[0]} to FY ${latestYearRef}`"
          subtitle="Budget movement per department"
          :noPad="true">
          <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
              <thead>
                <tr class="border-b-2 border-gray-100">
                  <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
                    Department
                  </th>
                  <template v-for="(yr, i) in yearsRef" :key="yr">
                    <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
                      FY {{ yr }}
                    </th>
                    <!-- Change column between consecutive years -->
                    <th v-if="i < yearsRef.length - 1"
                      class="text-center px-2 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
                      {{ String(yr).slice(2) }}→{{ String(yearsRef[i + 1]).slice(2) }}
                    </th>
                  </template>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in (yoyRows ?? [])" :key="r.sheet_code"
                  class="border-b border-gray-50 hover:bg-[#0D2137]/[0.02] transition-colors">
                  <td class="px-5 py-3 font-semibold text-[#0D2137] text-[12px]">{{ r.department }}</td>
                  <template v-for="(yr, i) in yearsRef" :key="yr">
                    <!-- Budget for this year -->
                    <td :class="['px-4 py-3 text-right font-mono text-[11px]',
                      yr === latestYearRef
                        ? 'font-bold text-[#0D2137] text-[12px]'
                        : 'text-gray-400']">
                      {{ r[`budget_${yr}`] ? phpM(r[`budget_${yr}`]) : '—' }}
                    </td>
                    <!-- Change badge between this year and the next -->
                    <td v-if="i < yearsRef.length - 1" class="px-2 py-3 text-center">
                      <span
                        v-if="r[`chg_${String(yr).slice(2)}_${String(yearsRef[i+1]).slice(2)}`] != null"
                        :class="['text-[10px] font-bold px-1.5 py-0.5 rounded-full border',
                          trendBg(r[`chg_${String(yr).slice(2)}_${String(yearsRef[i+1]).slice(2)}`]),
                          trendColor(r[`chg_${String(yr).slice(2)}_${String(yearsRef[i+1]).slice(2)}`])]">
                        {{ pct(r[`chg_${String(yr).slice(2)}_${String(yearsRef[i+1]).slice(2)}`]) }}
                      </span>
                      <span v-else class="text-gray-200 text-[10px]">—</span>
                    </td>
                  </template>
                </tr>
              </tbody>
            </table>
          </div>
        </SectionCard>
      </div>

    </div>
  </AppLayout>
</template>