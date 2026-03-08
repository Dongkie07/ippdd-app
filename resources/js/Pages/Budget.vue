<script setup>
import { ref, computed } from 'vue'
import AppLayout    from '@/Layouts/AppLayout.vue'
import SectionCard  from '@/Components/SectionCard.vue'
import YearSelector from '@/Components/YearSelector.vue'
import { Bar, Line } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement,
  LineElement, PointElement, Title, Tooltip, Legend
} from 'chart.js'
ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend)

// ── Props ─────────────────────────────────────────────────────
const props = defineProps({
  allByYear:  Object,  // { 2024: [...], 2025: [...], 2026: [...] }
  yearTotals: Object,  // { 2024: {...}, 2025: {...}, 2026: {...} }
  yoyRows:    Array,   // matched rows across years
  gainers:    Array,
  losers:     Array,
  fundMix:    Array,
})

// ── State ─────────────────────────────────────────────────────
const activeTab = ref('ranking')   // ranking | fundmix | yoy | movers
const search    = ref('')
const sortBy    = ref('budget_2026')
const sortDir   = ref('desc')

// ── Formatters ────────────────────────────────────────────────
const php  = v => v == null ? '—' : '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const phpM = v => v == null ? '—' : '₱' + (v / 1e6).toFixed(2) + 'M'
const pct  = v => v == null ? '—' : (v > 0 ? '+' : '') + v.toFixed(1) + '%'

// ── Year summary cards ────────────────────────────────────────
const yt = computed(() => props.yearTotals ?? {})

// ── YoY trend line chart (totals) ────────────────────────────
const trendData = computed(() => {
  const labels = ['FY 2024', 'FY 2025', 'FY 2026']
  const totals = [2024,2025,2026].map(y => yt.value[y]?.total ?? 0)
  const f101   = [2024,2025,2026].map(y => yt.value[y]?.f101  ?? 0)
  const f164   = [2024,2025,2026].map(y => yt.value[y]?.f164  ?? 0)
  return {
    labels,
    datasets: [
      { label: 'Total Budget',       data: totals, borderColor: '#0D2137', backgroundColor: 'rgba(13,33,55,0.07)', fill: true,  tension: 0.3, pointRadius: 6, pointBackgroundColor: '#0D2137', borderWidth: 2.5 },
      { label: 'Fund 101 (GAA)',      data: f101,   borderColor: '#C9A84C', backgroundColor: 'transparent',        fill: false, tension: 0.3, pointRadius: 4, pointBackgroundColor: '#C9A84C', borderWidth: 2, borderDash: [5,4] },
      { label: 'Fund 164 (Fiduciary)',data: f164,   borderColor: '#1E8449', backgroundColor: 'transparent',        fill: false, tension: 0.3, pointRadius: 4, pointBackgroundColor: '#1E8449', borderWidth: 2, borderDash: [3,3] },
    ]
  }
})
const trendOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { labels: { color: '#6B7280', font: { size: 11 }, boxWidth: 12, padding: 16 } }, tooltip: { callbacks: { label: c => '  ₱' + c.raw.toLocaleString('en-PH') } } },
  scales: {
    x: { ticks: { color: '#9CA3AF' }, grid: { color: '#F3F4F6' } },
    y: { ticks: { color: '#9CA3AF', callback: v => '₱' + (v/1e6).toFixed(0) + 'M' }, grid: { color: '#F3F4F6' } }
  }
}

// ── Fund mix stacked bar ──────────────────────────────────────
const fundMixData = computed(() => {
  const rows = props.fundMix ?? []
  const labels = rows.map(r => r.department.length > 22 ? r.department.slice(0,21)+'…' : r.department)
  return {
    labels,
    datasets: [
      { label: 'Fund 101', data: rows.map(r => r.f101_2026 ?? 0), backgroundColor: '#0D2137' },
      { label: 'Fund 164', data: rows.map(r => r.f164_2026 ?? 0), backgroundColor: '#C9A84C' },
      { label: 'Fund 161', data: rows.map(r => r.f161_2026 ?? 0), backgroundColor: '#1E8449' },
      { label: 'Fund 163', data: rows.map(r => r.f163_2026 ?? 0), backgroundColor: '#2E86C1' },
    ]
  }
})
const fundMixOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: 'top', labels: { color: '#6B7280', font: { size: 11 }, boxWidth: 10, padding: 14 } }, tooltip: { callbacks: { label: c => '  ' + c.dataset.label + ': ₱' + c.raw.toLocaleString('en-PH') } } },
  scales: {
    x: { stacked: true, ticks: { color: '#9CA3AF', font: { size: 9 } }, grid: { display: false } },
    y: { stacked: true, ticks: { color: '#9CA3AF', callback: v => '₱' + (v/1e6).toFixed(0) + 'M' }, grid: { color: '#F3F4F6' } }
  }
}

// ── YoY bar chart (top 15 depts, 3 years) ────────────────────
const yoyChartData = computed(() => {
  const rows = (props.yoyRows ?? []).filter(r => r.budget_2026).slice(0,15)
  return {
    labels: rows.map(r => r.department.length > 20 ? r.department.slice(0,19)+'…' : r.department),
    datasets: [
      { label: 'FY 2024', data: rows.map(r => r.budget_2024 ?? 0), backgroundColor: 'rgba(13,33,55,0.25)', borderRadius: 3 },
      { label: 'FY 2025', data: rows.map(r => r.budget_2025 ?? 0), backgroundColor: 'rgba(13,33,55,0.55)', borderRadius: 3 },
      { label: 'FY 2026', data: rows.map(r => r.budget_2026 ?? 0), backgroundColor: '#0D2137', borderRadius: 3 },
    ]
  }
})
const yoyChartOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: 'top', labels: { color: '#6B7280', font: { size: 11 }, boxWidth: 10, padding: 14 } }, tooltip: { callbacks: { label: c => '  ' + c.dataset.label + ': ₱' + c.raw.toLocaleString('en-PH') } } },
  scales: {
    x: { ticks: { color: '#9CA3AF', font: { size: 9 } }, grid: { display: false } },
    y: { ticks: { color: '#9CA3AF', callback: v => '₱' + (v/1e6).toFixed(0) + 'M' }, grid: { color: '#F3F4F6' } }
  }
}

// ── Tree expand state ────────────────────────────────────────
const expandedRows = ref(new Set())
const toggleExpand = (dept) => {
  if (expandedRows.value.has(dept)) expandedRows.value.delete(dept)
  else expandedRows.value.add(dept)
  expandedRows.value = new Set(expandedRows.value) // trigger reactivity
}
const expandAll   = () => { expandedRows.value = new Set(parentRows.value.filter(r => r._hasChildren).map(r => r.department)) }
const collapseAll = () => { expandedRows.value = new Set() }

// ── Build tree from yoyRows ───────────────────────────────────
const allRows2025 = computed(() => (props.allByYear?.[2025] ?? []))
const allRows2026 = computed(() => (props.allByYear?.[2026] ?? []))

// Children lookup by parent name (from live DB rows)
const childrenByParent = computed(() => {
  const map = {}
  // Use both 2025 and 2026 children, preferring 2026
  ;[...allRows2025.value, ...allRows2026.value].forEach(r => {
    if (r.parent_dept) {
      const pk = r.parent_dept.toUpperCase()
      if (!map[pk]) map[pk] = {}
      // key by dept name to deduplicate
      map[pk][r.department.toUpperCase()] = r
    }
  })
  // Convert to arrays
  const result = {}
  Object.keys(map).forEach(pk => { result[pk] = Object.values(map[pk]) })
  return result
})

// For a parent row, sum its own budget + all children budgets per year
const parentWithTotals = (r) => {
  const key = r.department.toUpperCase()
  const children = childrenByParent.value[key] ?? []

  // Children budgets from allByYear for each year
  const childSum = (yr) => {
    const yearRows = (props.allByYear?.[yr] ?? [])
    return children.reduce((sum, c) => {
      const match = yearRows.find(y => y.department.toUpperCase() === c.department.toUpperCase())
      return sum + (match?.budget_total ?? 0)
    }, 0)
  }

  const own2024 = r.budget_2024 ?? 0
  const own2025 = r.budget_2025 ?? 0
  const own2026 = r.budget_2026 ?? 0
  const subs2024 = childSum(2024)
  const subs2025 = childSum(2025)
  const subs2026 = childSum(2026)

  const combined2025 = own2025 + subs2025
  const combined2026 = own2026 + subs2026
  const chgCombined = combined2025 > 0 && combined2026 > 0
    ? Math.round((combined2026 - combined2025) / combined2025 * 1000) / 10
    : r.chg_25_26

  return {
    ...r,
    _isChild: false,
    _hasChildren: children.length > 0,
    _childCount: children.length,
    // Own = what the parent row itself has in the DB
    _own_2024: own2024,
    _own_2025: own2025,
    _own_2026: own2026,
    // Combined = own + all children
    _combined_2024: own2024 + subs2024,
    _combined_2025: combined2025,
    _combined_2026: combined2026,
    _chg_combined: chgCombined,
    // Override display budget fields with combined totals
    budget_2024: own2024 + subs2024 || r.budget_2024,
    budget_2025: combined2025 || r.budget_2025,
    budget_2026: combined2026 || r.budget_2026,
    chg_25_26:   chgCombined,
  }
}

const parentRows = computed(() => {
  const q = search.value.toLowerCase()
  return (props.yoyRows ?? []).filter(r => {
    if (q) return r.department.toLowerCase().includes(q) || r.sheet_code?.toLowerCase().includes(q)
    return true
  }).map(r => parentWithTotals(r))
})

// Flat list interleaving parents + their children
const filteredTree = computed(() => {
  const q = search.value.toLowerCase()
  const result = []
  parentRows.value.forEach(parent => {
    result.push(parent)
    if (parent._hasChildren) {
      const key = parent.department.toUpperCase()
      const children = childrenByParent.value[key] ?? []
      children.forEach(child => {
        const yoyChild = (props.yoyRows ?? []).find(y =>
          y.department.toUpperCase() === child.department.toUpperCase()
        )
        // Get per-year budgets from allByYear for accurate child data
        const b2024 = (props.allByYear?.[2024] ?? []).find(y => y.department.toUpperCase() === child.department.toUpperCase())?.budget_total ?? null
        const b2025 = (props.allByYear?.[2025] ?? []).find(y => y.department.toUpperCase() === child.department.toUpperCase())?.budget_total ?? child.budget_total ?? null
        const b2026 = (props.allByYear?.[2026] ?? []).find(y => y.department.toUpperCase() === child.department.toUpperCase())?.budget_total ?? null
        const chg = b2025 && b2026 ? Math.round((b2026 - b2025) / b2025 * 1000) / 10 : null

        const merged = {
          department:  child.department,
          sheet_code:  child.sheet_code ?? '',
          budget_2024: b2024,
          budget_2025: b2025,
          budget_2026: b2026,
          chg_25_26:   chg,
          f101_2026:   child.budget_fund_101 ?? yoyChild?.f101_2026 ?? 0,
          f164_2026:   child.budget_fund_164 ?? yoyChild?.f164_2026 ?? 0,
        }
        if (!q || merged.department.toLowerCase().includes(q)) {
          result.push({ ...merged, _isChild: true, _parentName: parent.department, _hasChildren: false, _childCount: 0 })
        }
      })
    }
  })
  return result
})

// ── Ranking table ─────────────────────────────────────────────
const rows = computed(() => {
  const q = search.value.toLowerCase()
  const list = (props.yoyRows ?? []).filter(r =>
    !q || r.department.toLowerCase().includes(q) || r.sheet_code.toLowerCase().includes(q)
  )
  const dir = sortDir.value === 'asc' ? 1 : -1
  return [...list].sort((a,b) => {
    const av = a[sortBy.value] ?? -Infinity
    const bv = b[sortBy.value] ?? -Infinity
    return av > bv ? dir : av < bv ? -dir : 0
  })
})

const setSort = k => {
  if (sortBy.value === k) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortBy.value = k; sortDir.value = 'desc' }
}
const si = k => sortBy.value === k ? (sortDir.value === 'asc' ? ' ↑' : ' ↓') : ''

const trendColor = v => v == null ? 'text-gray-300' : v > 0 ? 'text-emerald-600' : v < 0 ? 'text-red-500' : 'text-gray-400'
const trendBg    = v => v == null ? '' : v > 0 ? 'bg-emerald-50 border-emerald-100' : v < 0 ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-100'

const tabs = [
  { id: 'ranking', label: 'Budget Ranking' },
  { id: 'fundmix', label: 'Fund Mix' },
  { id: 'yoy',     label: '3-Year Comparison' },
  { id: 'movers',  label: 'Top Movers' },
]
</script>

<template>
  <AppLayout>
    <template #breadcrumb>Department Breakdown</template>
    <template #title>Department Breakdown</template>
    <template #subtitle>Budget analysis across all departments · FY 2024–2026</template>

    <div class="space-y-5">

      <!-- ── Year summary KPI row ───────────────────────────── -->
      <div class="grid grid-cols-3 gap-4">
        <div v-for="yr in [2024,2025,2026]" :key="yr"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
          <div class="flex items-center justify-between mb-3">
            <span class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">FY {{ yr }}</span>
            <span class="text-[10px] font-bold text-gray-400 bg-gray-50 border border-gray-100 rounded-full px-2 py-0.5">
              {{ yt[yr]?.dept_count ?? 0 }} offices
            </span>
          </div>
          <p class="text-[22px] font-extrabold text-[#0D2137] leading-none">
            {{ yt[yr] ? phpM(yt[yr].total) : '—' }}
          </p>
          <div class="mt-3 space-y-1.5">
            <div v-for="[label, key, color] in [['F101','f101','#0D2137'],['F164','f164','#C9A84C'],['F161','f161','#1E8449'],['F163','f163','#2E86C1']]" :key="key"
              class="flex items-center gap-2">
              <div class="w-1.5 h-1.5 rounded-full shrink-0" :style="{background: color}" />
              <span class="text-[10px] text-gray-400 w-8">{{ label }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-1 overflow-hidden">
                <div class="h-full rounded-full" :style="{
                  width: yt[yr]?.total > 0 ? ((yt[yr]?.[key] ?? 0) / yt[yr].total * 100) + '%' : '0%',
                  background: color
                }" />
              </div>
              <span class="text-[10px] font-bold text-gray-500 w-10 text-right">
                {{ yt[yr]?.total > 0 ? ((yt[yr]?.[key] ?? 0) / yt[yr].total * 100).toFixed(1) + '%' : '—' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── 3-Year trend line ──────────────────────────────── -->
      <SectionCard title="Budget Trend" subtitle="Total budget · Fund 101 · Fund 164 across FY 2024–2026">
        <div class="h-[180px]"><Line :data="trendData" :options="trendOpts" /></div>
      </SectionCard>

      <!-- ── Tab navigation ─────────────────────────────────── -->
      <div class="flex items-center gap-1 bg-gray-100 rounded-2xl p-1 w-fit">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
          :class="['px-4 py-2 rounded-xl text-[12px] font-bold transition-all',
            activeTab === tab.id
              ? 'bg-white text-[#0D2137] shadow-sm'
              : 'text-gray-400 hover:text-gray-600']">
          {{ tab.label }}
        </button>
      </div>

      <!-- ══ TAB: BUDGET RANKING ════════════════════════════════ -->
      <div v-if="activeTab === 'ranking'">
        <SectionCard
          :title="`Budget Ranking — ${parentRows.length} departments`"
          subtitle="Click a row to expand sub-offices · FY 2024 / 2025 / 2026"
          :noPad="true">
          <template #actions>
            <div class="flex items-center gap-2">
              <button @click="expandAll" class="text-[11px] font-bold text-[#0D2137] hover:text-[#C9A84C] transition-colors px-2 py-1 rounded-lg hover:bg-gray-50">Expand All</button>
              <span class="text-gray-200 text-xs">|</span>
              <button @click="collapseAll" class="text-[11px] font-bold text-gray-400 hover:text-gray-600 transition-colors px-2 py-1 rounded-lg hover:bg-gray-50">Collapse</button>
              <div class="relative ml-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                </svg>
                <input v-model="search" type="text" placeholder="Search office…"
                  class="pl-8 pr-4 py-2 text-[12px] border border-gray-200 rounded-xl w-48
                         focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 bg-gray-50/50
                         text-gray-600 placeholder-gray-300" />
              </div>
            </div>
          </template>

          <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
              <thead>
                <tr class="border-b-2 border-gray-100">
                  <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-8">#</th>
                  <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department / Office</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">FY 2024</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">FY 2025</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">FY 2026</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">25→26</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">F101</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">F164</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(r, i) in filteredTree" :key="r.sheet_code + r.department">
                  <!-- Parent row -->
                  <tr v-if="!r._isChild"
                    @click="r._hasChildren ? toggleExpand(r.department) : null"
                    :class="[
                      'border-b border-gray-100 transition-colors',
                      r._hasChildren ? 'cursor-pointer hover:bg-blue-50/40' : 'hover:bg-gray-50/60',
                      expandedRows.has(r.department) ? 'bg-[#0D2137]/[0.03]' : ''
                    ]">
                    <td class="px-5 py-3 text-gray-300 font-mono text-[10px] font-bold">{{ String(i+1).padStart(2,'0') }}</td>
                    <td class="px-5 py-3">
                      <div class="flex items-center gap-2">
                        <!-- Expand chevron -->
                        <span v-if="r._hasChildren"
                          :class="['w-4 h-4 rounded flex items-center justify-center transition-transform shrink-0',
                            expandedRows.has(r.department) ? 'text-[#0D2137] rotate-90' : 'text-gray-300']">
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                        </span>
                        <span v-else class="w-4 shrink-0" />
                        <span class="bg-[#0D2137]/6 text-[#0D2137] text-[9px] font-mono font-bold px-1.5 py-0.5 rounded border border-[#0D2137]/10 shrink-0">{{ r.sheet_code }}</span>
                        <span class="font-bold text-[#0D2137] text-[12px]">{{ r.department }}</span>
                        <span v-if="r._hasChildren" class="text-[9px] font-bold text-blue-400 bg-blue-50 border border-blue-100 px-1.5 py-0.5 rounded-full">
                          {{ r._childCount }} sub-offices
                        </span>
                      </div>
                    </td>
                    <!-- FY 2024 -->
                    <td class="px-4 py-3 text-right">
                      <template v-if="r._hasChildren && r._combined_2024 > 0">
                        <p class="font-mono font-bold text-gray-500 text-[11px]">{{ phpM(r._combined_2024) }}</p>
                        <p v-if="r._own_2024 > 0" class="font-mono text-[9px] text-gray-300 mt-0.5">own {{ phpM(r._own_2024) }}</p>
                      </template>
                      <span v-else class="font-mono text-gray-400 text-[11px]">{{ r.budget_2024 ? phpM(r.budget_2024) : '—' }}</span>
                    </td>
                    <!-- FY 2025 -->
                    <td class="px-4 py-3 text-right">
                      <template v-if="r._hasChildren && r._combined_2025 > 0">
                        <p class="font-mono font-bold text-gray-600 text-[11px]">{{ phpM(r._combined_2025) }}</p>
                        <p v-if="r._own_2025 > 0" class="font-mono text-[9px] text-gray-300 mt-0.5">own {{ phpM(r._own_2025) }}</p>
                      </template>
                      <span v-else class="font-mono text-gray-500 text-[11px]">{{ r.budget_2025 ? phpM(r.budget_2025) : '—' }}</span>
                    </td>
                    <!-- FY 2026 -->
                    <td class="px-4 py-3 text-right">
                      <template v-if="r._hasChildren && r._combined_2026 > 0">
                        <p class="font-mono font-bold text-[#0D2137] text-[12px]">{{ phpM(r._combined_2026) }}</p>
                        <p v-if="r._own_2026 > 0" class="font-mono text-[9px] text-gray-400 mt-0.5">own {{ phpM(r._own_2026) }}</p>
                      </template>
                      <span v-else class="font-mono font-bold text-[#0D2137] text-[12px]">{{ r.budget_2026 ? phpM(r.budget_2026) : '—' }}</span>
                    </td>
                    <!-- Change -->
                    <td class="px-4 py-3 text-right">
                      <span v-if="r.chg_25_26 != null" :class="['text-[11px] font-bold px-2 py-0.5 rounded-full border', trendBg(r.chg_25_26), trendColor(r.chg_25_26)]">{{ pct(r.chg_25_26) }}</span>
                      <span v-else class="text-gray-300 text-[11px]">—</span>
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-gray-400 text-[11px]">{{ r.f101_2026 > 0 ? phpM(r.f101_2026) : '—' }}</td>
                    <td class="px-4 py-3 text-right font-mono text-gray-400 text-[11px]">{{ r.f164_2026 > 0 ? phpM(r.f164_2026) : '—' }}</td>
                  </tr>

                  <!-- Child rows (sub-offices) -->
                  <tr v-if="r._isChild && expandedRows.has(r._parentName)"
                    :key="'child-' + r.department"
                    class="border-b border-gray-50 bg-slate-50/60 hover:bg-blue-50/20 transition-colors">
                    <td class="px-5 py-2.5" />
                    <td class="py-2.5 pl-14 pr-4">
                      <div class="flex items-center gap-2">
                        <span class="w-1 h-1 rounded-full bg-[#0D2137]/20 shrink-0" />
                        <span class="text-[9px] font-mono font-bold text-gray-300 border border-gray-200 px-1.5 py-0.5 rounded shrink-0">{{ r.sheet_code }}</span>
                        <span class="font-medium text-gray-600 text-[12px]">{{ r.department }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-2.5 text-right font-mono text-gray-300 text-[11px]">{{ r.budget_2024 ? phpM(r.budget_2024) : '—' }}</td>
                    <td class="px-4 py-2.5 text-right font-mono text-gray-400 text-[11px]">{{ r.budget_2025 ? phpM(r.budget_2025) : '—' }}</td>
                    <td class="px-4 py-2.5 text-right font-mono font-semibold text-gray-600 text-[12px]">{{ r.budget_2026 ? phpM(r.budget_2026) : '—' }}</td>
                    <td class="px-4 py-2.5 text-right">
                      <span v-if="r.chg_25_26 != null" :class="['text-[10px] font-bold px-1.5 py-0.5 rounded-full border', trendBg(r.chg_25_26), trendColor(r.chg_25_26)]">{{ pct(r.chg_25_26) }}</span>
                      <span v-else class="text-gray-200 text-[10px]">—</span>
                    </td>
                    <td class="px-4 py-2.5 text-right font-mono text-gray-400 text-[11px]">{{ r.f101_2026 > 0 ? phpM(r.f101_2026) : '—' }}</td>
                    <td class="px-4 py-2.5 text-right font-mono text-gray-400 text-[11px]">{{ r.f164_2026 > 0 ? phpM(r.f164_2026) : '—' }}</td>
                  </tr>
                </template>

                <tr v-if="filteredTree.length === 0">
                  <td colspan="8" class="px-5 py-12 text-center text-gray-300 text-sm">No results found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </SectionCard>
      </div>

      <!-- ══ TAB: FUND MIX ══════════════════════════════════════ -->
      <div v-if="activeTab === 'fundmix'">
        <SectionCard title="Fund Mix per Department" subtitle="FY 2026 · Fund 101 / 164 / 161 / 163 stacked · top 15 departments">
          <div class="h-[380px]"><Bar :data="fundMixData" :options="fundMixOpts" /></div>
        </SectionCard>

        <!-- Fund mix detail table -->
        <SectionCard class="mt-4" title="Fund Breakdown Detail" subtitle="FY 2026 · all fund clusters per department" :noPad="true">
          <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
              <thead>
                <tr class="border-b-2 border-gray-100">
                  <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Total</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Fund 101</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Fund 164</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Fund 161</th>
                  <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Fund 163</th>
                  <th class="px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-36">Mix</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in yoyRows.filter(x => x.budget_2026)" :key="r.sheet_code"
                  class="border-b border-gray-50 hover:bg-[#0D2137]/[0.02] transition-colors">
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                      <span class="bg-[#0D2137]/6 text-[#0D2137] text-[9px] font-mono font-bold px-1.5 py-0.5 rounded border border-[#0D2137]/10 shrink-0">{{ r.sheet_code }}</span>
                      <span class="font-semibold text-[#0D2137] text-[12px]">{{ r.department }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-right font-mono font-bold text-[#0D2137]">{{ phpM(r.budget_2026) }}</td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">{{ r.f101_2026 > 0 ? phpM(r.f101_2026) : '—' }}</td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">{{ r.f164_2026 > 0 ? phpM(r.f164_2026) : '—' }}</td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">{{ r.f161_2026 > 0 ? phpM(r.f161_2026) : '—' }}</td>
                  <td class="px-4 py-3 text-right font-mono text-gray-500">{{ r.f163_2026 > 0 ? phpM(r.f163_2026) : '—' }}</td>
                  <td class="px-4 py-3">
                    <!-- Mini stacked bar -->
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

      <!-- ══ TAB: 3-YEAR COMPARISON ═════════════════════════════ -->
      <div v-if="activeTab === 'yoy'">
        <SectionCard title="3-Year Budget Comparison" subtitle="Top 15 departments by FY 2026 budget · FY 2024 / 2025 / 2026 side by side">
          <div class="h-[380px]"><Bar :data="yoyChartData" :options="yoyChartOpts" /></div>
        </SectionCard>

        <!-- YoY change table -->
        <SectionCard class="mt-4" title="Year-over-Year Changes" subtitle="Budget movement per department across all 3 years" :noPad="true">
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
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                      <span class="bg-[#0D2137]/6 text-[#0D2137] text-[9px] font-mono font-bold px-1.5 py-0.5 rounded border border-[#0D2137]/10 shrink-0">{{ r.sheet_code }}</span>
                      <span class="font-semibold text-[#0D2137] text-[12px]">{{ r.department }}</span>
                    </div>
                  </td>
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

      <!-- ══ TAB: TOP MOVERS ════════════════════════════════════ -->
      <div v-if="activeTab === 'movers'">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

          <!-- Gainers -->
          <SectionCard title="🟢 Top Budget Gainers" subtitle="Largest % increase FY 2025 → 2026">
            <div class="space-y-3">
              <div v-for="(r,i) in gainers" :key="r.sheet_code"
                class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                <span class="w-7 h-7 rounded-full bg-emerald-100 border-2 border-emerald-200 flex items-center justify-center text-[11px] font-extrabold text-emerald-700 shrink-0">{{ i+1 }}</span>
                <div class="flex-1 min-w-0">
                  <p class="font-bold text-[#0D2137] text-[12px] truncate">{{ r.department }}</p>
                  <p class="text-[11px] text-gray-500 mt-0.5">
                    {{ r.budget_2025 ? phpM(r.budget_2025) : '—' }} → <span class="font-bold text-[#0D2137]">{{ r.budget_2026 ? phpM(r.budget_2026) : '—' }}</span>
                  </p>
                </div>
                <span class="text-[13px] font-extrabold text-emerald-600 shrink-0">{{ pct(r.chg_25_26) }}</span>
              </div>
              <p v-if="!gainers?.length" class="text-center text-gray-300 text-sm py-4">No data yet</p>
            </div>
          </SectionCard>

          <!-- Losers -->
          <SectionCard title="🔴 Top Budget Decreases" subtitle="Largest % decrease FY 2025 → 2026">
            <div class="space-y-3">
              <div v-for="(r,i) in losers" :key="r.sheet_code"
                class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100">
                <span class="w-7 h-7 rounded-full bg-red-100 border-2 border-red-200 flex items-center justify-center text-[11px] font-extrabold text-red-700 shrink-0">{{ i+1 }}</span>
                <div class="flex-1 min-w-0">
                  <p class="font-bold text-[#0D2137] text-[12px] truncate">{{ r.department }}</p>
                  <p class="text-[11px] text-gray-500 mt-0.5">
                    {{ r.budget_2025 ? phpM(r.budget_2025) : '—' }} → <span class="font-bold text-[#0D2137]">{{ r.budget_2026 ? phpM(r.budget_2026) : '—' }}</span>
                  </p>
                </div>
                <span class="text-[13px] font-extrabold text-red-600 shrink-0">{{ pct(r.chg_25_26) }}</span>
              </div>
              <p v-if="!losers?.length" class="text-center text-gray-300 text-sm py-4">No data yet</p>
            </div>
          </SectionCard>
        </div>

        <!-- YoY % change bar chart -->
        <SectionCard class="mt-4" title="Budget Change % — FY 2025 to 2026" subtitle="Positive = budget increase · Negative = budget decrease">
          <div class="h-[320px]">
            <Bar :data="{
              labels: yoyRows.filter(r => r.chg_25_26 != null).slice(0,20).map(r => r.department.length > 18 ? r.department.slice(0,17)+'…' : r.department),
              datasets: [{
                label: '% Change 2025→2026',
                data: yoyRows.filter(r => r.chg_25_26 != null).slice(0,20).map(r => r.chg_25_26),
                backgroundColor: yoyRows.filter(r => r.chg_25_26 != null).slice(0,20).map(r => r.chg_25_26 >= 0 ? 'rgba(30,132,73,0.75)' : 'rgba(169,50,38,0.75)'),
                borderRadius: 4,
              }]
            }" :options="{
              responsive: true, maintainAspectRatio: false,
              plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => '  ' + (c.raw > 0 ? '+' : '') + c.raw.toFixed(1) + '%' } } },
              scales: {
                x: { ticks: { color: '#9CA3AF', font: { size: 9 } }, grid: { display: false } },
                y: { ticks: { color: '#9CA3AF', callback: v => (v > 0 ? '+' : '') + v + '%' }, grid: { color: '#F3F4F6' } }
              }
            }" />
          </div>
        </SectionCard>
      </div>

    </div>
  </AppLayout>
</template>