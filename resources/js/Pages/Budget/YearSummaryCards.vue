<script setup>
/**
 * Budget/YearSummaryCards.vue
 * Fiscal-year KPI cards with fund breakdown bars.
 */
import { FUNDS } from '@/constants/wfp'
import { useFormatters } from '@/composables/useFormatters'

defineProps({
  yearTotals: { type: Object, default: () => ({}) },
  years: { type: Array, default: () => [] },
})

const { phpM } = useFormatters()
const percentOfTotal = (summary, fundKey) => {
  if (!summary?.total) return 0
  return ((summary[fundKey] ?? 0) / summary.total) * 100
}
</script>

<template>
  <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    <div
      v-for="year in years"
      :key="year"
      class="interactive-card rounded-2xl border border-[#DDEDE3] bg-white p-4 shadow-[0_12px_32px_rgba(6,78,59,0.08)]"
    >
      <div class="flex items-center justify-between mb-3">
        <span class="text-[10px] font-black uppercase tracking-[0.14em] text-[#8FA79B]">FY {{ year }}</span>
        <span class="rounded-full border border-[#DDEDE3] bg-[#F8FCF9] px-2 py-0.5 text-[10px] font-black text-[#64746B]">
          {{ yearTotals[year]?.dept_count ?? 0 }} offices
        </span>
      </div>

      <p class="text-[22px] font-extrabold text-[#064E3B] leading-none">
        {{ yearTotals[year] ? phpM(yearTotals[year].total) : '—' }}
      </p>

      <div class="mt-3 space-y-1.5">
        <div v-for="fund in FUNDS" :key="fund.key" class="flex items-center gap-2">
          <div class="w-1.5 h-1.5 rounded-full shrink-0" :style="{ background: fund.color }" />
          <span class="text-[10px] text-gray-400 w-8">{{ fund.key.toUpperCase() }}</span>
          <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#E6F2EA]">
            <div
              class="h-full rounded-full"
              :style="{
                width: `${percentOfTotal(yearTotals[year], fund.key)}%`,
                background: fund.color,
              }"
            />
          </div>
          <span class="text-[10px] font-bold text-gray-500 w-10 text-right">
            {{ yearTotals[year]?.total > 0 ? `${percentOfTotal(yearTotals[year], fund.key).toFixed(1)}%` : '—' }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
