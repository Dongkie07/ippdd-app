<script setup>
import { ref, computed } from 'vue'
import AppLayout    from '@/Layouts/AppLayout.vue'
import SectionCard  from '@/Components/SectionCard.vue'
import YearSelector from '@/Components/YearSelector.vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale,
  BarElement, Title, Tooltip, Legend
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const props = defineProps({
  yearSummary: Array,
  deptData:    Object,
  allDepts:    Array,
})

const selectedYear = ref(2026)

// All depts sorted for horizontal bar
const depts = computed(() =>
  [...(props.deptData[selectedYear.value] ?? [])].sort((a, b) => a.budget - b.budget)
)

const horizontalBarData = computed(() => ({
  labels: depts.value.map(d => d.department.length > 28 ? d.department.slice(0, 28) + '…' : d.department),
  datasets: [{
    label: 'Budget (₱)',
    data: depts.value.map(d => d.budget),
    backgroundColor: depts.value.map((_, i) => `hsl(${210 + i * 4}, 80%, ${52 + i * 1.5}%)`),
    borderRadius: 4,
    borderSkipped: false,
  }]
}))

const horizontalBarOptions = {
  indexAxis: 'y',
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: ctx => '  ₱' + ctx.raw.toLocaleString() } }
  },
  scales: {
    x: {
      ticks: { color: '#9CA3AF', callback: v => '₱' + (v / 1_000_000).toFixed(0) + 'M' },
      grid: { color: '#F3F4F6' }
    },
    y: { ticks: { color: '#374151', font: { size: 11 } }, grid: { display: false } }
  }
}

// Year comparison table
const yearComparison = computed(() => {
  const all = props.allDepts
  const deptNames = [...new Set(all.map(d => d.department))]
  return deptNames.map(dept => {
    const row = { department: dept }
    for (const yr of [2024, 2025, 2026]) {
      const found = all.find(d => d.department === dept && d.year === yr)
      row[yr] = found?.budget ?? null
    }
    // growth from 2024→2026
    if (row[2024] && row[2026]) {
      row.growth = +((row[2026] - row[2024]) / row[2024] * 100).toFixed(1)
    } else {
      row.growth = null
    }
    return row
  }).filter(r => r[selectedYear.value]).sort((a, b) => (b[selectedYear.value] ?? 0) - (a[selectedYear.value] ?? 0))
})

const php  = v => v ? '₱' + v.toLocaleString('en-PH') : '—'
const phpM = v => v ? '₱' + (v / 1_000_000).toFixed(2) + 'M' : '—'
</script>

<template>
  <AppLayout>
    <template #header-title>Budget by Department </template>
    <template #header-subtitle>Detailed financial breakdown per office</template>

    <div class="space-y-6">

      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-display font-bold text-gray-800">Department Budget Analysis</h2>
          <p class="text-sm text-gray-400 mt-0.5">All offices ranked by allocated budget</p>
        </div>
        <YearSelector v-model="selectedYear" />
      </div>

      <!-- Horizontal Bar -->
      <SectionCard :title="`All Departments — FY ${selectedYear}`" subtitle="Sorted by budget allocation (ascending)">
        <div :style="`height: ${Math.max(300, depts.length * 36)}px`">
          <Bar :data="horizontalBarData" :options="horizontalBarOptions" />
        </div>
      </SectionCard>

      <!-- Year Comparison Table -->
      <SectionCard title="3-Year Budget Comparison" subtitle="Side-by-side view: 2024 vs 2025 vs 2026" :noPad="true">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-widest">
                <th class="text-left px-6 py-3 font-medium">Department</th>
                <th class="text-right px-6 py-3 font-medium">2024</th>
                <th class="text-right px-6 py-3 font-medium">2025</th>
                <th class="text-right px-6 py-3 font-medium">2026</th>
                <th class="text-right px-6 py-3 font-medium">2024→2026</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in yearComparison"
                :key="row.department"
                class="border-b border-gray-50 hover:bg-[#F8FAFF] transition-colors"
              >
                <td class="px-6 py-3 font-medium text-gray-700">{{ row.department }}</td>
                <td class="px-6 py-3 text-right text-gray-500 font-mono text-xs">{{ phpM(row[2024]) }}</td>
                <td class="px-6 py-3 text-right text-gray-500 font-mono text-xs">{{ phpM(row[2025]) }}</td>
                <td class="px-6 py-3 text-right font-mono text-xs font-semibold text-gray-700">{{ phpM(row[2026]) }}</td>
                <td class="px-6 py-3 text-right">
                  <span v-if="row.growth !== null" :class="[
                    'text-xs font-semibold px-2 py-0.5 rounded-full',
                    row.growth >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'
                  ]">
                    {{ row.growth >= 0 ? '▲' : '▼' }} {{ Math.abs(row.growth) }}%
                  </span>
                  <span v-else class="text-xs text-gray-300">New</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </SectionCard>
    </div>
  </AppLayout>
</template>