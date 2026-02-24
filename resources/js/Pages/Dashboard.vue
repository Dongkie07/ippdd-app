<script setup>
import { ref, computed } from 'vue'
import AppLayout   from '@/Layouts/AppLayout.vue'
import KpiCard     from '@/Components/KpiCard.vue'
import SectionCard from '@/Components/SectionCard.vue'
import YearSelector from '@/Components/YearSelector.vue'
import { Bar, Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale,
  BarElement, LineElement, PointElement, ArcElement,
  Title, Tooltip, Legend, Filler
} from 'chart.js'

ChartJS.register(
  CategoryScale, LinearScale, BarElement, LineElement,
  PointElement, ArcElement, Title, Tooltip, Legend, Filler
)

const props = defineProps({
  yearSummary: Array,
  deptData:    Object,
  allDepts:    Array,
})

// ── State ──────────────────────────────────────────────────────
const selectedYear = ref(2026)
const searchQuery  = ref('')
const sortKey      = ref('budget')
const sortDir      = ref('desc')

// ── KPI Computeds ──────────────────────────────────────────────
const cur  = computed(() => props.yearSummary.find(y => y.year === selectedYear.value) ?? {})
const prev = computed(() => props.yearSummary.find(y => y.year === selectedYear.value - 1) ?? null)

const budgetTrend = computed(() => {
  if (!prev.value?.total_budget) return null
  return +((cur.value.total_budget - prev.value.total_budget) / prev.value.total_budget * 100).toFixed(1)
})
const piTrend = computed(() => {
  if (!prev.value?.total_pi) return null
  return +((cur.value.total_pi - prev.value.total_pi) / prev.value.total_pi * 100).toFixed(1)
})

// ── Bar Chart ──────────────────────────────────────────────────
const barData = computed(() => {
  const depts = props.deptData[selectedYear.value] ?? []
  return {
    labels: depts.map(d => d.department.length > 18 ? d.department.slice(0, 18) + '…' : d.department),
    datasets: [{
      data: depts.map(d => d.budget),
      backgroundColor: '#1E90FF',
      hoverBackgroundColor: '#60B0FF',
      borderRadius: 8,
      borderSkipped: false,
    }]
  }
})
const barOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ₱' + ctx.raw.toLocaleString() } } },
  scales: {
    x: { ticks: { color: '#9CA3AF', font: { size: 10 } }, grid: { display: false } },
    y: { ticks: { color: '#9CA3AF', callback: v => '₱' + (v / 1_000_000).toFixed(0) + 'M' }, grid: { color: '#F3F4F6' } }
  }
}

// ── Line Chart ─────────────────────────────────────────────────
const lineData = computed(() => ({
  labels: props.yearSummary.map(y => y.year.toString()),
  datasets: [
    {
      label: 'Total Budget (₱)',
      data: props.yearSummary.map(y => y.total_budget),
      borderColor: '#1E90FF',
      backgroundColor: 'rgba(30,144,255,0.08)',
      fill: true, tension: 0.4,
      pointBackgroundColor: '#1E90FF', pointRadius: 5,
    },
    {
      label: 'Performance Indicators',
      data: props.yearSummary.map(y => y.total_pi * 500000),
      borderColor: '#F59E0B',
      backgroundColor: 'rgba(245,158,11,0)',
      fill: false, tension: 0.4, borderDash: [6, 4],
      pointBackgroundColor: '#F59E0B', pointRadius: 5,
    }
  ]
}))
const lineOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend: { labels: { color: '#6B7280', font: { size: 11 }, boxWidth: 12, padding: 16 } },
    tooltip: { callbacks: { label: ctx => ctx.datasetIndex === 0 ? ' ₱' + ctx.raw.toLocaleString() : ' ' + (ctx.raw / 500000) + ' PIs' } }
  },
  scales: {
    x: { ticks: { color: '#9CA3AF' }, grid: { color: '#F3F4F6' } },
    y: { ticks: { color: '#9CA3AF', callback: v => '₱' + (v / 1_000_000).toFixed(0) + 'M' }, grid: { color: '#F3F4F6' } }
  }
}

// ── Doughnut ───────────────────────────────────────────────────
const doughnutData = computed(() => {
  const top5 = (props.deptData[selectedYear.value] ?? []).slice(0, 5)
  return {
    labels: top5.map(d => d.department.length > 20 ? d.department.slice(0, 20) + '…' : d.department),
    datasets: [{
      data: top5.map(d => d.budget),
      backgroundColor: ['#1E90FF', '#F59E0B', '#10B981', '#8B5CF6', '#EF4444'],
      borderColor: '#fff', borderWidth: 3,
    }]
  }
})
const doughnutOptions = {
  responsive: true, maintainAspectRatio: false, cutout: '65%',
  plugins: {
    legend: { position: 'bottom', labels: { color: '#6B7280', font: { size: 10 }, padding: 10, boxWidth: 10 } },
    tooltip: { callbacks: { label: ctx => ' ₱' + ctx.raw.toLocaleString() } }
  }
}

// ── Table ──────────────────────────────────────────────────────
const filteredDepts = computed(() => {
  const q = searchQuery.value.toLowerCase()
  const list = props.allDepts
    .filter(d => d.year === selectedYear.value)
    .filter(d => !q || d.department.toLowerCase().includes(q) || d.sheet_code.toLowerCase().includes(q))

  return [...list].sort((a, b) => {
    const val = sortDir.value === 'asc' ? 1 : -1
    if (a[sortKey.value] > b[sortKey.value]) return val
    if (a[sortKey.value] < b[sortKey.value]) return -val
    return 0
  })
})

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'desc' }
}

const maxBudget = computed(() =>
  Math.max(...(props.deptData[selectedYear.value] ?? []).map(d => d.budget), 1)
)

// ── Helpers ────────────────────────────────────────────────────
const php = v => '₱' + v.toLocaleString('en-PH')
const phpM = v => '₱' + (v / 1_000_000).toFixed(2) + 'M'
</script>

<template>
  <AppLayout>
    <template #header-title>Dashboard Overview</template>
    <template #header-subtitle>Davao del Norte State College · Work & Financial Plan</template>

    <div class="space-y-6">

      <!-- ── Page Top ────────────────────────────────────────── -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-display font-bold text-gray-800">Financial Overview</h2>
          <p class="text-sm text-gray-400 mt-0.5">Budget allocation and performance indicators across all offices</p>
        </div>
        <YearSelector v-model="selectedYear" />
      </div>

      <!-- ── KPI Cards ──────────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <KpiCard
          label="Total Budget"
          :value="phpM(cur.total_budget ?? 0)"
          :trend="budgetTrend"
          :sub="budgetTrend !== null ? `vs ${selectedYear - 1}` : 'No prior year'"
          color="blue"
          icon="peso"
        />
        <KpiCard
          label="Performance Indicators"
          :value="(cur.total_pi ?? 0).toLocaleString()"
          :trend="piTrend"
          :sub="piTrend !== null ? `vs ${selectedYear - 1}` : 'Active PIs'"
          color="green"
          icon="target"
        />
        <KpiCard
          label="Departments"
          :value="(cur.dept_count ?? 0).toString()"
          :sub="`offices tracked in ${selectedYear}`"
          color="amber"
          icon="building"
        />
        <KpiCard
          label="Avg Budget / Dept"
          :value="cur.dept_count ? phpM(cur.total_budget / cur.dept_count) : '₱0'"
          :sub="`per office allocation`"
          color="rose"
          icon="chart"
        />
      </div>

      <!-- ── Charts Row ─────────────────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Bar Chart -->
        <SectionCard
          title="Top 10 Departments by Budget"
          :subtitle="`Fiscal Year ${selectedYear}`"
          class="lg:col-span-2"
        >
          <div class="h-64">
            <Bar :data="barData" :options="barOptions" />
          </div>
        </SectionCard>

        <!-- Doughnut -->
        <SectionCard title="Budget Distribution" subtitle="Top 5 departments">
          <div class="h-64">
            <Doughnut :data="doughnutData" :options="doughnutOptions" />
          </div>
        </SectionCard>
      </div>

      <!-- ── Trend Line ─────────────────────────────────────── -->
      <SectionCard title="3-Year Budget Trend" subtitle="2024 – 2026 comparison with PI count overlay">
        <div class="h-52">
          <Line :data="lineData" :options="lineOptions" />
        </div>
      </SectionCard>

      <!-- ── Department Table ───────────────────────────────── -->
      <SectionCard title="Department Breakdown" :subtitle="`${filteredDepts.length} offices — FY ${selectedYear}`" :noPad="true">
        <template #actions>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search…"
            class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 w-48 focus:outline-none focus:ring-2 focus:ring-[#1E90FF]/30 focus:border-[#1E90FF] text-gray-600 placeholder-gray-300"
          />
        </template>

        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-widest">
              <th class="text-left px-6 py-3 font-medium">#</th>
              <th class="text-left px-6 py-3 font-medium">
                <button @click="setSort('department')" class="flex items-center gap-1 hover:text-gray-600">
                  Department <span>{{ sortKey === 'department' ? (sortDir === 'asc' ? '↑' : '↓') : '' }}</span>
                </button>
              </th>
              <th class="text-left px-6 py-3 font-medium">Code</th>
              <th class="text-right px-6 py-3 font-medium">
                <button @click="setSort('budget')" class="flex items-center gap-1 hover:text-gray-600 ml-auto">
                  Budget <span>{{ sortKey === 'budget' ? (sortDir === 'asc' ? '↑' : '↓') : '' }}</span>
                </button>
              </th>
              <th class="text-right px-6 py-3 font-medium">
                <button @click="setSort('pi_count')" class="flex items-center gap-1 hover:text-gray-600 ml-auto">
                  PIs <span>{{ sortKey === 'pi_count' ? (sortDir === 'asc' ? '↑' : '↓') : '' }}</span>
                </button>
              </th>
              <th class="px-6 py-3 font-medium w-36">Share</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(dept, i) in filteredDepts"
              :key="dept.sheet_code + i"
              class="border-b border-gray-50 hover:bg-[#F8FAFF] transition-colors"
            >
              <td class="px-6 py-3.5 text-gray-300 font-mono text-xs">{{ String(i + 1).padStart(2, '0') }}</td>
              <td class="px-6 py-3.5 font-medium text-gray-700">{{ dept.department }}</td>
              <td class="px-6 py-3.5">
                <span class="inline-block bg-blue-50 text-[#1E90FF] text-xs font-mono px-2 py-0.5 rounded-md border border-blue-100">
                  {{ dept.sheet_code }}
                </span>
              </td>
              <td class="px-6 py-3.5 text-right font-mono text-gray-700 font-medium">{{ php(dept.budget) }}</td>
              <td class="px-6 py-3.5 text-right text-gray-500">{{ dept.pi_count }}</td>
              <td class="px-6 py-3.5">
                <div class="flex items-center gap-2">
                  <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                    <div
                      class="bg-[#1E90FF] h-1.5 rounded-full transition-all duration-500"
                      :style="{ width: Math.round(dept.budget / maxBudget * 100) + '%' }"
                    />
                  </div>
                  <span class="text-xs text-gray-400 w-8 text-right">
                    {{ Math.round(dept.budget / maxBudget * 100) }}%
                  </span>
                </div>
              </td>
            </tr>
            <tr v-if="filteredDepts.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-gray-300 text-sm">No departments found.</td>
            </tr>
          </tbody>
        </table>
      </SectionCard>

    </div>
  </AppLayout>
</template>