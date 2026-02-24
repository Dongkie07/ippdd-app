<script setup>
import AppLayout   from '@/Layouts/AppLayout.vue'
import SectionCard from '@/Components/SectionCard.vue'
import { ref } from 'vue'

const reports = [
  { title: 'Budget Summary Report',       desc: 'Total budget per department for the selected year.',          icon: 'chart', format: 'PDF' },
  { title: 'Performance Indicators Report', desc: 'All PIs per office with success indicators and targets.',   icon: 'target', format: 'PDF' },
  { title: 'Year-Over-Year Comparison',   desc: 'Side-by-side budget comparison across 2024, 2025, 2026.',    icon: 'trend', format: 'XLSX' },
  { title: 'Full WFP Data Export',        desc: 'Complete raw data export of all WFP entries.',               icon: 'table', format: 'XLSX' },
]

const selectedYear = ref(2026)
const generating   = ref(null)

const generate = (i) => {
  generating.value = i
  // TODO: call /reports/generate with type + year
  setTimeout(() => { generating.value = null }, 2000)
}
</script>

<template>
  <AppLayout>
    <template #header-title>Reports & Export</template>
    <template #header-subtitle>Generate PDF reports and Excel exports</template>

    <div class="space-y-6 max-w-3xl">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-display font-bold text-gray-800">Generate Reports</h2>
          <p class="text-sm text-gray-400 mt-0.5">Export dashboard data as PDF or Excel</p>
        </div>
        <!-- Year pick -->
        <select v-model="selectedYear" class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#1E90FF]/30">
          <option value="2024">2024</option>
          <option value="2025">2025</option>
          <option value="2026">2026</option>
        </select>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div
          v-for="(report, i) in reports"
          :key="i"
          class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow group"
        >
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#F0F5FF] flex items-center justify-center text-[#1E90FF] shrink-0">
              <svg v-if="report.icon === 'chart'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 18V10M8 18V6M13 18v-4M18 18V8"/><line x1="3" y1="18" x2="21" y2="18"/>
              </svg>
              <svg v-if="report.icon === 'target'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1" fill="currentColor"/>
              </svg>
              <svg v-if="report.icon === 'trend'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
              </svg>
              <svg v-if="report.icon === 'table'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/>
                <line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/>
              </svg>
            </div>
            <div class="flex-1">
              <div class="flex items-center justify-between">
                <p class="font-semibold text-gray-700 text-sm">{{ report.title }}</p>
                <span :class="[
                  'text-xs font-mono px-2 py-0.5 rounded-md font-bold',
                  report.format === 'PDF' ? 'bg-red-50 text-red-500' : 'bg-green-50 text-green-600'
                ]">{{ report.format }}</span>
              </div>
              <p class="text-xs text-gray-400 mt-1 leading-relaxed">{{ report.desc }}</p>
              <button
                @click="generate(i)"
                :disabled="generating === i"
                class="mt-3 text-xs font-semibold text-[#1E90FF] hover:underline flex items-center gap-1 disabled:opacity-50"
              >
                <svg v-if="generating === i" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ generating === i ? 'Generating…' : `Generate ${report.format} for ${selectedYear}` }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Notice -->
      <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-start gap-3">
        <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p class="text-xs text-amber-700">
          <span class="font-semibold">PDF export</span> requires installing <code class="bg-amber-100 px-1 rounded">barryvdh/laravel-dompdf</code> via Composer.
          Excel export uses <code class="bg-amber-100 px-1 rounded">maatwebsite/excel</code>. See the architecture guide for setup instructions.
        </p>
      </div>
    </div>
  </AppLayout>
</template>