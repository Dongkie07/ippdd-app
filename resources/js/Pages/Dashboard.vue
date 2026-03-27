<script setup>
/**
 * Pages/Dashboard.vue — Financial Overview
 * ─────────────────────────────────────────────────────────────
 * Thin page component — just wires props to sub-components.
 *
 * Sub-components (all in Pages/Dashboard/):
 *   KpiRow.vue      — 4 KPI cards
 *   ChartsRow.vue   — bar chart + donut chart
 *   TrendChart.vue  — 3-year line chart
 *   OfficeTable.vue — expandable dept table
 *
 * Composables:
 *   useFormatters   — php(), phpM()
 *   useTableSort    — setSort, applySortTo
 *   useExpandRows   — toggleExpand, isExpanded
 *   useChartConfigs — barData/Opts, lineData/Opts, donutData/Opts
 *
 * DATA FLOW:
 *   DashboardController.php → Inertia props → computed rows → sub-components
 */
import { ref, computed, watch } from 'vue'
import AppLayout   from '@/Layouts/AppLayout.vue'
import AiInsightsPanel from '@/Components/AiInsightsPanel.vue'
import { Bar, Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement,
  LineElement, PointElement, ArcElement, Title, Tooltip, Legend, Filler
} from 'chart.js'

// ── Dashboard sub-components ──────────────────────────────────
import KpiRow      from './Dashboard/KpiRow.vue'
import ChartsRow   from './Dashboard/ChartsRow.vue'
import TrendChart  from './Dashboard/TrendChart.vue'
import OfficeTable from './Dashboard/OfficeTable.vue'

// ── Composables ───────────────────────────────────────────────
import { useTableSort }    from '@/composables/useTableSort'
import { useExpandRows }   from '@/composables/useExpandRows'
import { useChartConfigs } from '@/composables/useChartConfigs'

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Title, Tooltip, Legend, Filler)

// ── Props (from DashboardController.php) ─────────────────────
const props = defineProps({
  yearSummary: Array,  // [{ year, total_budget, dept_count, total_101…163 }]
  deptData:    Object, // { 2024: [...top10], 2025: [...], 2026: [...] }
  allDepts:    Array,  // top-level depts with children[] nested inside
})

// ── Selected year state ───────────────────────────────────────
const year = ref(props.yearSummary?.at(-1)?.year ?? 2026)

// ── Available years (dynamic — grows when new files are uploaded) ─
const availableYears = computed(() =>
  props.yearSummary?.map(y => y.year) ?? []
)

// ── Year summary helpers ──────────────────────────────────────
const cur  = computed(() => props.yearSummary?.find(y => y.year === year.value) ?? {})
const prev = computed(() => props.yearSummary?.find(y => y.year === year.value - 1) ?? null)

// ── Table: sort + dedup rows ──────────────────────────────────
const { applySortTo, setSort } = useTableSort('budget_total', 'desc')
const { collapseAll } = useExpandRows()

// Collapse expanded rows when year changes
watch(year, () => collapseAll())

const rows = computed(() => {
  // Deduplicate by dept name (Map avoids Vue reactivity issues with Set)
  const dedupMap = new Map()
  ;(props.allDepts ?? [])
    .filter(d => d.year === year.value)
    .forEach(d => { if (!dedupMap.has(d.department)) dedupMap.set(d.department, d) })
  return applySortTo(Array.from(dedupMap.values()))
})

// ── Chart data ────────────────────────────────────────────────
const { barData, barOpts, lineData, lineOpts, donutData, donutOpts } =
  useChartConfigs(props, year)
</script>

<template>
  <AppLayout>
    <template #breadcrumb>Dashboard Overview</template>
    <template #title>Financial Overview</template>
    <template #subtitle>Davao del Norte State College · Annual Work & Financial Plan · FY 2024–2026</template>

    <div class="space-y-5">

      <!-- Top bar: offices reporting + year selector with + button -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2 bg-[#0D2137]/5 border border-[#0D2137]/10 rounded-xl px-3.5 py-2">
          <svg class="w-3.5 h-3.5 text-[#C9A84C]" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
          </svg>
          <span class="text-[12px] font-bold text-[#0D2137]">{{ cur.dept_count ?? 0 }} Offices Reporting</span>
        </div>

        <!-- Year selector with + New Year button -->
        <div class="flex items-center gap-2">
          <div class="flex gap-1.5 bg-gray-100 rounded-xl p-1">
            <button v-for="y in availableYears" :key="y" @click="year = y"
              :class="['px-3.5 py-1.5 rounded-lg text-[13px] font-bold transition-all',
                year === y
                  ? 'bg-white text-[#0D2137] shadow-sm'
                  : 'text-gray-400 hover:text-gray-600']">
              {{ y }}
            </button>
          </div>
          <!-- + button → goes to Upload page to add a new fiscal year -->
          <a href="/upload"
            class="w-8 h-8 rounded-xl bg-[#0D2137] text-white flex items-center justify-center
                   hover:bg-[#1A5276] transition-colors shadow-sm"
            title="Upload data for a new fiscal year">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path d="M12 5v14M5 12h14"/>
            </svg>
          </a>
        </div>
      </div>

      <!-- KPI cards -->
      <KpiRow :cur="cur" :prev="prev" :year="year" />

      <!-- Bar + Donut charts -->
      <ChartsRow
        :barData="barData" :barOpts="barOpts"
        :donutData="donutData" :donutOpts="donutOpts"
        :year="year" />

      <!-- 3-year trend line -->
      <TrendChart :lineData="lineData" :lineOpts="lineOpts" />

      <!-- AI Insights -->
      <AiInsightsPanel :year="year" />

      <!-- Office breakdown table -->
      <OfficeTable :rows="rows" :year="year" />

    </div>
  </AppLayout>
</template>