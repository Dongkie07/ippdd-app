<script setup>
import { ref, computed } from 'vue'
import AppLayout    from '@/Layouts/AppLayout.vue'
import SectionCard  from '@/Components/SectionCard.vue'
import YearSelector from '@/Components/YearSelector.vue'
import { Radar, Bubble } from 'vue-chartjs'
import {
  Chart as ChartJS, RadialLinearScale, PointElement,
  LineElement, Filler, Tooltip, Legend,
  BubbleController, LinearScale
} from 'chart.js'

ChartJS.register(RadialLinearScale, PointElement, LineElement, Filler, Tooltip, Legend, BubbleController, LinearScale)

const props = defineProps({
  yearSummary: Array,
  deptData:    Object,
  allDepts:    Array,
})

const selectedYear = ref(2026)

const topDepts = computed(() =>
  (props.deptData[selectedYear.value] ?? []).slice(0, 8)
)

// Radar: budget vs PI count
const radarData = computed(() => ({
  labels: topDepts.value.map(d => d.department.length > 14 ? d.department.slice(0, 14) + '…' : d.department),
  datasets: [
    {
      label: 'Budget (normalized)',
      data: topDepts.value.map(d => {
        const max = Math.max(...topDepts.value.map(x => x.budget))
        return Math.round((d.budget / max) * 100)
      }),
      borderColor: '#1E90FF', backgroundColor: 'rgba(30,144,255,0.15)',
      pointBackgroundColor: '#1E90FF', borderWidth: 2,
    },
    {
      label: 'PI Count (normalized)',
      data: topDepts.value.map(d => {
        const max = Math.max(...topDepts.value.map(x => x.pi_count))
        return Math.round((d.pi_count / max) * 100)
      }),
      borderColor: '#F59E0B', backgroundColor: 'rgba(245,158,11,0.1)',
      pointBackgroundColor: '#F59E0B', borderWidth: 2,
    }
  ]
}))

const radarOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { labels: { color: '#6B7280', font: { size: 11 } } } },
  scales: {
    r: {
      grid: { color: '#E5E7EB' },
      pointLabels: { color: '#374151', font: { size: 10 } },
      ticks: { display: false },
      min: 0, max: 100,
    }
  }
}

// Efficiency table: budget-per-PI
const efficiencyList = computed(() =>
  (props.deptData[selectedYear.value] ?? [])
    .map(d => ({
      ...d,
      budgetPerPi: d.pi_count > 0 ? Math.round(d.budget / d.pi_count) : null,
      efficiency:  d.pi_count > 0 ? (d.pi_count / (d.budget / 1_000_000)).toFixed(2) : null
    }))
    .sort((a, b) => (b.efficiency ?? 0) - (a.efficiency ?? 0))
)

const php = v => v !== null ? '₱' + v.toLocaleString('en-PH') : '—'
</script>

<template>
  <AppLayout>
    <template #header-title>Performance Indicators</template>
    <template #header-subtitle>PI count, budget efficiency, and department targets</template>

    <div class="space-y-6">

      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-display font-bold text-gray-800">Performance Indicators</h2>
          <p class="text-sm text-gray-400 mt-0.5">Comparing budget allocation vs number of performance indicators per office</p>
        </div>
        <YearSelector v-model="selectedYear" />
      </div>

      <!-- Summary pills -->
      <div class="flex gap-3 flex-wrap">
        <div v-for="y in yearSummary" :key="y.year"
          :class="['flex items-center gap-3 px-4 py-3 rounded-xl border transition-all',
            y.year === selectedYear ? 'bg-[#0B1F3A] text-white border-[#0B1F3A]' : 'bg-white text-gray-600 border-gray-200']"
        >
          <span class="text-sm font-bold">{{ y.year }}</span>
          <span :class="['text-xs', y.year === selectedYear ? 'text-blue-200' : 'text-gray-400']">
            {{ y.total_pi }} PIs · {{ y.dept_count }} depts
          </span>
        </div>
      </div>

      <!-- Radar -->
      <SectionCard title="Budget vs PI Count Radar" subtitle="Normalized 0–100 scale for top 8 departments">
        <div class="h-80">
          <Radar :data="radarData" :options="radarOptions" />
        </div>
      </SectionCard>

      <!-- Efficiency table -->
      <SectionCard title="Budget Efficiency Ranking" :subtitle="`PIs per ₱1M — FY ${selectedYear}`" :noPad="true">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-widest">
              <th class="text-left px-6 py-3 font-medium">Rank</th>
              <th class="text-left px-6 py-3 font-medium">Department</th>
              <th class="text-right px-6 py-3 font-medium">PI Count</th>
              <th class="text-right px-6 py-3 font-medium">Total Budget</th>
              <th class="text-right px-6 py-3 font-medium">Budget / PI</th>
              <th class="text-right px-6 py-3 font-medium">PIs per ₱1M</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(dept, i) in efficiencyList"
              :key="dept.sheet_code"
              class="border-b border-gray-50 hover:bg-[#F8FAFF] transition-colors"
            >
              <td class="px-6 py-3.5">
                <span :class="[
                  'w-6 h-6 rounded-full inline-flex items-center justify-center text-xs font-bold',
                  i === 0 ? 'bg-amber-100 text-amber-600' :
                  i === 1 ? 'bg-gray-100 text-gray-500' :
                  i === 2 ? 'bg-orange-100 text-orange-600' :
                  'bg-gray-50 text-gray-400'
                ]">{{ i + 1 }}</span>
              </td>
              <td class="px-6 py-3.5 font-medium text-gray-700">{{ dept.department }}</td>
              <td class="px-6 py-3.5 text-right text-gray-600">{{ dept.pi_count }}</td>
              <td class="px-6 py-3.5 text-right font-mono text-xs text-gray-500">{{ php(dept.budget) }}</td>
              <td class="px-6 py-3.5 text-right font-mono text-xs text-gray-500">{{ php(dept.budgetPerPi) }}</td>
              <td class="px-6 py-3.5 text-right">
                <span v-if="dept.efficiency" class="inline-block bg-blue-50 text-[#1E90FF] text-xs font-semibold px-2 py-0.5 rounded-lg font-mono">
                  {{ dept.efficiency }}
                </span>
                <span v-else class="text-gray-300 text-xs">N/A</span>
              </td>
            </tr>
          </tbody>
        </table>
      </SectionCard>

    </div>
  </AppLayout>
</template>