<script setup>
/**
 * Pages/Dashboard.vue — IPPDD Executive Dashboard
 * Thin page component — wires props to sub-components.
 */
import { ref, computed, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import AiInsightsPanel from '@/Components/AiInsightsPanel.vue'
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
import { useFormatters }    from '@/composables/useFormatters'
import { useTableSort }     from '@/composables/useTableSort'
import { useExpandRows }    from '@/composables/useExpandRows'
import { useChartConfigs }  from '@/composables/useChartConfigs'

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Title, Tooltip, Legend, Filler)

// ── Props (from DashboardController.php) ─────────────────────
const props = defineProps({
  yearSummary: Array,  // [{ year, total_budget, dept_count, total_101…163 }]
  deptData:    Object, // { 2024: [...top10], 2025: [...], 2026: [...] }
  allDepts:    Array,  // top-level depts with children[] nested inside
})

const { phpM } = useFormatters()

// ── Selected year state ───────────────────────────────────────
const year = ref(props.yearSummary?.at(-1)?.year ?? 2026)

// ── Available years (dynamic — grows when new files are uploaded) ─
const availableYears = computed(() =>
  props.yearSummary?.map(y => y.year) ?? []
)

// ── Year summary helpers ──────────────────────────────────────
const cur  = computed(() => props.yearSummary?.find(y => y.year === year.value) ?? {})
const prev = computed(() => props.yearSummary?.find(y => y.year === year.value - 1) ?? null)

const totalAcrossYears = computed(() =>
  (props.yearSummary ?? []).reduce((sum, item) => sum + Number(item.total_budget ?? 0), 0)
)

const yearRangeLabel = computed(() => {
  if (!availableYears.value.length) return 'No fiscal years yet'
  return `FY ${availableYears.value[0]}–${availableYears.value.at(-1)}`
})

// ── Table: sort + dedup rows ──────────────────────────────────
const { applySortTo } = useTableSort('budget_total', 'desc')
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
    <template #breadcrumb>
      <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#168A4A]">
        DNSC · IPPDD
      </p>
    </template>
    <template #title>Institutional Planning and Project Development Division (IPPDD) Executive Dashboard</template>
    <template #subtitle>
      Annual Work & Financial Plan Intelligence Hub · {{ yearRangeLabel }}
    </template>

    <div class="space-y-5">
      <!-- Executive hero summary -->
      <section class="relative overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#064E3B] via-[#075D3F] to-[#022C22] p-6 text-white shadow-[0_24px_70px_rgba(6,78,59,0.28)]">
        <div class="pointer-events-none absolute inset-0 opacity-35">
          <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full border border-[#53D28C]/30" />
          <div class="absolute right-8 top-8 h-40 w-40 rounded-full bg-[#53D28C]/20 blur-3xl" />
          <div class="absolute bottom-0 right-0 h-44 w-44 rounded-full border border-white/15" />
        </div>

        <div class="relative grid gap-6 lg:grid-cols-[1.5fr_.9fr] lg:items-end">
          <div>
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[11px] font-black uppercase tracking-[0.16em] text-[#DDFBE8] backdrop-blur">
              <span class="h-2 w-2 rounded-full bg-[#53D28C] shadow-[0_0_18px_rgba(83,210,140,.9)]" />
              Executive Budget Monitor
            </div>
            <h2 class="max-w-4xl font-display text-2xl font-black leading-tight tracking-tight sm:text-3xl">
              Institutional Planning and Project Development Division (IPPDD) Executive Dashboard
            </h2>
            <p class="mt-3 max-w-3xl text-sm font-medium leading-6 text-[#DDFBE8]/85">
              A cleaner financial command center for tracking WFP allocations, office rankings, fund distribution, and year-over-year movement without forcing humans to stare at spreadsheet chaos. Society may yet recover.
            </p>
          </div>

          <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
            <div class="rounded-2xl border border-white/12 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#B7F4CE]/80">Selected Year</p>
              <p class="mt-1 text-2xl font-black tracking-tight text-white">FY {{ year }}</p>
            </div>
            <div class="rounded-2xl border border-white/12 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#B7F4CE]/80">Current Allocation</p>
              <p class="mt-1 text-2xl font-black tracking-tight text-white">{{ phpM(cur.total_budget ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-white/12 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#B7F4CE]/80">All Years Tracked</p>
              <p class="mt-1 text-2xl font-black tracking-tight text-white">{{ phpM(totalAcrossYears) }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Top bar: offices reporting + year selector with + button -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 rounded-2xl border border-[#DDEDE3] bg-white px-4 py-2.5 shadow-sm">
          <div class="grid h-8 w-8 place-items-center rounded-xl bg-[#ECFDF3] text-[#168A4A]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-3M9 9h1M9 13h1M9 17h1M14 13h1M14 17h1" />
            </svg>
          </div>
          <div>
            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#8FA79B]">Offices Reporting</p>
            <p class="text-[13px] font-black text-[#064E3B]">{{ cur.dept_count ?? 0 }} offices · FY {{ year }}</p>
          </div>
        </div>

        <!-- Year selector with + New Year button -->
        <div class="flex items-center gap-2 rounded-2xl border border-[#DDEDE3] bg-white p-1.5 shadow-sm">
          <button v-for="y in availableYears" :key="y" @click="year = y"
            :class="['rounded-xl px-4 py-2 text-[13px] font-black transition-all',
              year === y
                ? 'bg-[#064E3B] text-white shadow-lg shadow-[#064E3B]/20'
                : 'text-[#8FA79B] hover:bg-[#ECFDF3] hover:text-[#064E3B]']">
            FY {{ y }}
          </button>
          <a href="/upload"
            class="grid h-9 w-9 place-items-center rounded-xl bg-[#168A4A] text-white shadow-sm transition-colors hover:bg-[#064E3B]"
            title="Upload data for a new fiscal year">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
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
