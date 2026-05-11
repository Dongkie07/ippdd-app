<script setup>
import { Bar } from 'vue-chartjs'
import SectionCard from '@/Components/SectionCard.vue'
import { useFormatters } from '@/composables/useFormatters'

defineProps({
  rows: { type: Array, default: () => [] },
  years: { type: Array, default: () => [] },
  latestYear: { type: [Number, String, null], default: null },
  chartData: { type: Object, required: true },
  chartOptions: { type: Object, required: true },
  trendColor: { type: Function, required: true },
  trendBg: { type: Function, required: true },
})

const { phpM, pct } = useFormatters()
const changeKey = (fromYear, toYear) => `chg_${String(fromYear).slice(2)}_${String(toYear).slice(2)}`
</script>

<template>
  <div class="space-y-4">
    <SectionCard
      title="Multi-Year Budget Comparison"
      :subtitle="`Top 15 departments by FY ${latestYear ?? '—'} budget`"
    >
      <div class="h-[380px]">
        <Bar :data="chartData" :options="chartOptions" />
      </div>
    </SectionCard>

    <SectionCard
      :title="`Year-over-Year Changes — FY ${years[0] ?? '—'} to FY ${latestYear ?? '—'}`"
      subtitle="Budget movement per department"
      :noPad="true"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-[12px]">
          <thead>
            <tr class="border-b-2 border-gray-100">
              <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
                Department
              </th>
              <template v-for="(year, index) in years" :key="year">
                <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
                  FY {{ year }}
                </th>
                <th
                  v-if="index < years.length - 1"
                  class="text-center px-2 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400"
                >
                  {{ String(year).slice(2) }}→{{ String(years[index + 1]).slice(2) }}
                </th>
              </template>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in rows"
              :key="row.office_key || row.sheet_code || row.department"
              class="border-b border-gray-50 hover:bg-[#064E3B]/[0.02] transition-colors"
            >
              <td class="px-5 py-3 font-semibold text-[#064E3B] text-[12px]">{{ row.department }}</td>

              <template v-for="(year, index) in years" :key="year">
                <td :class="[
                  'px-4 py-3 text-right font-mono text-[11px]',
                  year === latestYear ? 'font-bold text-[#064E3B] text-[12px]' : 'text-gray-400',
                ]">
                  {{ row[`budget_${year}`] ? phpM(row[`budget_${year}`]) : '—' }}
                </td>

                <td v-if="index < years.length - 1" class="px-2 py-3 text-center">
                  <span
                    v-if="row[changeKey(year, years[index + 1])] != null"
                    :class="[
                      'text-[10px] font-bold px-1.5 py-0.5 rounded-full border',
                      trendBg(row[changeKey(year, years[index + 1])]),
                      trendColor(row[changeKey(year, years[index + 1])]),
                    ]"
                  >
                    {{ pct(row[changeKey(year, years[index + 1])]) }}
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
</template>
