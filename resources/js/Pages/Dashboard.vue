<script setup>
import { ref, computed } from 'vue'
import AppLayout       from '@/Layouts/AppLayout.vue'
import KpiCard         from '@/Components/KpiCard.vue'
import SectionCard     from '@/Components/SectionCard.vue'
import YearSelector    from '@/Components/YearSelector.vue'
import AiInsightsPanel from '@/Components/AiInsightsPanel.vue'
import { Bar, Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement,
  LineElement, PointElement, ArcElement, Title, Tooltip, Legend, Filler
} from 'chart.js'
ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Title, Tooltip, Legend, Filler)

// ── Props ─────────────────────────────────────────────────────
const props = defineProps({
  yearSummary: Array,   // [{ year, total_budget, dept_count, total_101, total_164, total_161, total_163 }]
  deptData:    Object,  // { 2024: [...top10...], 2025: [...], 2026: [...] }
  allDepts:    Array,   // flat list for table
})

// ── State ─────────────────────────────────────────────────────
const year    = ref(props.yearSummary?.at(-1)?.year ?? 2026)
const search  = ref('')
const sortBy  = ref('budget_total')
const sortDir = ref('desc')

// ── Current & previous year summaries ────────────────────────
const cur  = computed(() => props.yearSummary?.find(y => y.year === year.value) ?? {})
const prev = computed(() => props.yearSummary?.find(y => y.year === year.value - 1))

const trend = (f) => {
  if (!prev.value?.[f] || !cur.value?.[f]) return null
  return +((cur.value[f] - prev.value[f]) / prev.value[f] * 100).toFixed(1)
}

const fundPct = (key) => {
  const t = cur.value.total_budget ?? 0
  return t > 0 ? +((cur.value[key] ?? 0) / t * 100).toFixed(1) : 0
}

// ── Bar chart — top 10 departments ───────────────────────────
const BAR_COLORS = ['#0D2137','#1A3A5C','#1E5C8A','#2478B4','#3A93C9','#55AEDE','#7DC4EC','#A3D8F5','#C9A84C','#E2C170']
const barData = computed(() => {
  const depts = props.deptData?.[year.value] ?? []
  return {
    labels:   depts.map(d => d.department.length > 20 ? d.department.slice(0, 19) + '…' : d.department),
    datasets: [{ data: depts.map(d => d.budget_total ?? d.budget ?? 0), backgroundColor: BAR_COLORS, borderRadius: 6, borderSkipped: false }]
  }
})
const barOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => '  ₱' + c.raw.toLocaleString('en-PH') } } },
  scales: {
    x: { ticks: { color: '#9CA3AF', font: { size: 10 } }, grid: { display: false } },
    y: { ticks: { color: '#9CA3AF', callback: v => '₱' + (v / 1e6).toFixed(0) + 'M', font: { size: 10 } }, grid: { color: '#F3F4F6' } }
  }
}

// ── Line chart — YoY total budget + fund breakdown ────────────
const lineData = computed(() => ({
  labels: props.yearSummary?.map(y => 'FY ' + y.year) ?? [],
  datasets: [
    {
      label: 'Total Budget',
      data: props.yearSummary?.map(y => y.total_budget) ?? [],
      borderColor: '#1A5276', backgroundColor: 'rgba(26,82,118,0.07)',
      fill: true, tension: 0.35, pointBackgroundColor: '#1A5276', pointRadius: 5, borderWidth: 2.5
    },
    {
      label: 'Fund 101 (GAA)',
      data: props.yearSummary?.map(y => y.total_101 ?? 0) ?? [],
      borderColor: '#C9A84C', backgroundColor: 'transparent',
      fill: false, tension: 0.35, borderDash: [5, 4],
      pointBackgroundColor: '#C9A84C', pointRadius: 4, borderWidth: 2
    },
    {
      label: 'Fund 164 (Fiduciary)',
      data: props.yearSummary?.map(y => y.total_164 ?? 0) ?? [],
      borderColor: '#1E8449', backgroundColor: 'transparent',
      fill: false, tension: 0.35, borderDash: [3, 3],
      pointBackgroundColor: '#1E8449', pointRadius: 4, borderWidth: 2
    }
  ]
}))
const lineOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend: { labels: { color: '#6B7280', font: { size: 11 }, boxWidth: 12, padding: 16 } },
    tooltip: { callbacks: { label: c => '  ₱' + c.raw.toLocaleString('en-PH') } }
  },
  scales: {
    x: { ticks: { color: '#9CA3AF' }, grid: { color: '#F3F4F6' } },
    y: { ticks: { color: '#9CA3AF', callback: v => '₱' + (v / 1e6).toFixed(0) + 'M' }, grid: { color: '#F3F4F6' } }
  }
}

// ── Doughnut — top 6 by budget ────────────────────────────────
const donutData = computed(() => {
  const top = (props.deptData?.[year.value] ?? []).slice(0, 6)
  return {
    labels: top.map(d => d.department.length > 22 ? d.department.slice(0, 21) + '…' : d.department),
    datasets: [{
      data: top.map(d => d.budget_total ?? d.budget ?? 0),
      backgroundColor: ['#0D2137','#1A5276','#C9A84C','#2E86C1','#1E8449','#A93226'],
      borderColor: '#fff', borderWidth: 3
    }]
  }
})
const donutOpts = {
  responsive: true, maintainAspectRatio: false, cutout: '68%',
  plugins: {
    legend: { position: 'bottom', labels: { color: '#374151', font: { size: 10 }, padding: 12, boxWidth: 10 } },
    tooltip: { callbacks: { label: c => '  ₱' + c.raw.toLocaleString('en-PH') } }
  }
}

// ── Department table ──────────────────────────────────────────
const rows = computed(() => {
  const q    = search.value.toLowerCase()
  const list = (props.allDepts ?? [])
    .filter(d => d.year === year.value)
    .filter(d => !q || d.department.toLowerCase().includes(q))
  const dir = sortDir.value === 'asc' ? 1 : -1
  return [...list].sort((a, b) => {
    const av = a[sortBy.value] ?? 0
    const bv = b[sortBy.value] ?? 0
    return av > bv ? dir : av < bv ? -dir : 0
  })
})

const maxBudget = computed(() =>
  Math.max(...(rows.value.map(d => d.budget_total ?? d.budget ?? 0)), 1)
)

const setSort = k => {
  if (sortBy.value === k) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortBy.value = k; sortDir.value = 'desc' }
}
const sortIcon = k => sortBy.value === k ? (sortDir.value === 'asc' ? ' ↑' : ' ↓') : ''

// ── Formatters ────────────────────────────────────────────────
const php  = v => '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const phpM = v => '₱' + (v / 1e6).toFixed(2) + 'M'

const bget = d => d.budget_total ?? d.budget ?? 0
const f101 = d => d.budget_fund_101 ?? d.fund_101 ?? 0
const f164 = d => d.budget_fund_164 ?? d.fund_164 ?? 0
</script>

<template>
  <AppLayout>
    <template #breadcrumb>Dashboard Overview</template>
    <template #title>Financial Overview</template>
    <template #subtitle>Davao del Norte State College · Annual Work & Financial Plan · FY 2024–2026</template>

    <div class="space-y-5">

      <!-- ── Top bar ─────────────────────────────────────────── -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2 bg-[#0D2137]/5 border border-[#0D2137]/10 rounded-xl px-3.5 py-2">
          <svg class="w-3.5 h-3.5 text-[#C9A84C]" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
          </svg>
          <span class="text-[12px] font-bold text-[#0D2137]">{{ cur.dept_count ?? 0 }} Offices Reporting</span>
        </div>
        <YearSelector v-model="year" :years="yearSummary?.map(y => y.year) ?? []" />
      </div>

      <!-- ── KPI Row ─────────────────────────────────────────── -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <KpiCard
          label="Total Budget Allocation"
          :value="phpM(cur.total_budget ?? 0)"
          :trend="trend('total_budget')"
          :sub="trend('total_budget') !== null ? `vs FY ${year - 1}` : ''"
          accent="navy" icon="peso" />

        <KpiCard
          label="Reporting Offices"
          :value="(cur.dept_count ?? 0).toString()"
          :sub="`offices in FY ${year}`"
          accent="green" icon="building" />

        <KpiCard
          label="Average per Office"
          :value="cur.dept_count ? phpM((cur.total_budget ?? 0) / cur.dept_count) : '₱0'"
          sub="budget per office"
          accent="navy" icon="chart" />

        <!-- Fund Mix card -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 flex flex-col gap-2">
          <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">Fund Mix — FY {{ year }}</p>
          <div class="space-y-2 mt-1">
            <div v-for="[label, key, color] in [
              ['Fund 101 — GAA',       'total_101', '#0D2137'],
              ['Fund 164 — Fiduciary', 'total_164', '#C9A84C'],
              ['Fund 161',             'total_161', '#1A5276'],
              ['Fund 163',             'total_163', '#1E8449'],
            ]" :key="key" class="flex items-center gap-2">
              <div class="w-2 h-2 rounded-full shrink-0" :style="{ background: color }" />
              <span class="text-[10px] text-gray-500 w-28 truncate">{{ label }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                <div class="h-full rounded-full transition-all"
                  :style="{ width: fundPct(key) + '%', background: color }" />
              </div>
              <span class="text-[10px] font-bold text-gray-600 w-8 text-right">{{ fundPct(key) }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Charts row ─────────────────────────────────────── -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <SectionCard class="xl:col-span-2" :title="`Top 10 Offices — FY ${year}`" subtitle="Ranked by total budget allocation">
          <div class="h-[260px]"><Bar :data="barData" :options="barOpts" /></div>
        </SectionCard>
        <SectionCard title="Budget Distribution" subtitle="Top 6 offices by share">
          <div class="h-[260px]"><Doughnut :data="donutData" :options="donutOpts" /></div>
        </SectionCard>
      </div>

      <!-- ── Trend line ──────────────────────────────────────── -->
      <SectionCard title="3-Year Budget Trend" subtitle="Total budget · Fund 101 (GAA) · Fund 164 (Fiduciary)">
        <div class="h-[200px]"><Line :data="lineData" :options="lineOpts" /></div>
      </SectionCard>

      <!-- ── AI Insights ─────────────────────────────────────── -->
      <AiInsightsPanel :year="year" />

      <!-- ── Department Table ───────────────────────────────── -->
      <SectionCard
        :title="`Office Breakdown — FY ${year}`"
        :subtitle="`${rows.length} offices · click headers to sort`"
        :noPad="true">
        <template #actions>
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400"
              fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            <input v-model="search" type="text" placeholder="Search office…"
              class="pl-8 pr-4 py-2 text-[12px] border border-gray-200 rounded-xl w-48
                     focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 focus:border-[#0D2137]/40
                     text-gray-600 placeholder-gray-300 bg-gray-50/50" />
          </div>
        </template>

        <div class="overflow-x-auto">
          <table class="w-full text-[13px]">
            <thead>
              <tr class="border-b-2 border-gray-100">
                <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-10">#</th>
                <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
                  <button @click="setSort('department')" class="hover:text-[#0D2137]">Department{{ sortIcon('department') }}</button>
                </th>
                <th class="text-right px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
                  <button @click="setSort('budget_total')" class="hover:text-[#0D2137] ml-auto flex items-center gap-1">
                    Total Budget{{ sortIcon('budget_total') }}
                  </button>
                </th>
                <th class="text-right px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
                  <button @click="setSort('budget_fund_101')" class="hover:text-[#0D2137] ml-auto flex items-center gap-1">
                    Fund 101{{ sortIcon('budget_fund_101') }}
                  </button>
                </th>
                <th class="text-right px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
                  <button @click="setSort('budget_fund_164')" class="hover:text-[#0D2137] ml-auto flex items-center gap-1">
                    Fund 164{{ sortIcon('budget_fund_164') }}
                  </button>
                </th>
                <th class="px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-36">Share</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(d, i) in rows" :key="(d.id ?? d.department) + i"
                class="border-b border-gray-50 hover:bg-[#0D2137]/[0.02] transition-colors group">
                <td class="px-6 py-3.5 text-gray-300 font-mono text-[11px] font-bold">{{ String(i + 1).padStart(2, '0') }}</td>
                <td class="px-6 py-3.5">
                  <span class="font-semibold text-[#0D2137] group-hover:text-[#1A5276] transition-colors">{{ d.department }}</span>
                </td>
                <td class="px-6 py-3.5 text-right">
                  <span class="font-mono font-bold text-[#0D2137] text-[13px]">{{ php(bget(d)) }}</span>
                </td>
                <td class="px-6 py-3.5 text-right font-mono text-[12px] text-gray-500">
                  {{ f101(d) > 0 ? php(f101(d)) : '—' }}
                </td>
                <td class="px-6 py-3.5 text-right font-mono text-[12px] text-gray-500">
                  {{ f164(d) > 0 ? php(f164(d)) : '—' }}
                </td>
                <td class="px-6 py-3.5">
                  <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                      <div class="h-full bg-[#0D2137] rounded-full transition-all duration-700"
                        :style="{ width: Math.round(bget(d) / maxBudget * 100) + '%' }" />
                    </div>
                    <span class="text-[10px] text-gray-400 font-bold tabular-nums w-7 text-right">
                      {{ Math.round(bget(d) / maxBudget * 100) }}%
                    </span>
                  </div>
                </td>
              </tr>
              <tr v-if="rows.length === 0">
                <td colspan="6" class="px-6 py-14 text-center text-gray-300 text-sm">No results found.</td>
              </tr>
            </tbody>
            <tfoot v-if="rows.length > 0">
              <tr class="border-t-2 border-gray-200 bg-[#0D2137]/[0.02]">
                <td colspan="2" class="px-6 py-3.5">
                  <span class="text-[11px] font-extrabold uppercase tracking-widest text-gray-500">Total</span>
                </td>
                <td class="px-6 py-3.5 text-right">
                  <span class="font-mono font-extrabold text-[#0D2137]">
                    {{ php(rows.reduce((s, d) => s + bget(d), 0)) }}
                  </span>
                </td>
                <td class="px-6 py-3.5 text-right font-mono text-[12px] text-gray-500">
                  {{ php(rows.reduce((s, d) => s + f101(d), 0)) }}
                </td>
                <td class="px-6 py-3.5 text-right font-mono text-[12px] text-gray-500">
                  {{ php(rows.reduce((s, d) => s + f164(d), 0)) }}
                </td>
                <td class="px-6 py-3.5" />
              </tr>
            </tfoot>
          </table>
        </div>
      </SectionCard>

    </div>
  </AppLayout>
</template>