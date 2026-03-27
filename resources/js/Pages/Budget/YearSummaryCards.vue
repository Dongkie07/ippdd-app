<script setup>
/**
 * Budget/YearSummaryCards.vue
 * ─────────────────────────────────────────
 * 3 KPI cards showing each fiscal year's total + fund breakdown bars.
 *
 * Props:
 *   yearTotals  Object — { 2024: { total, dept_count, f101, f164, f161, f163 }, ... }
 */
import { useFormatters } from '@/composables/useFormatters'

defineProps({ yearTotals: { type: Object, default: () => ({}) } })

const { phpM } = useFormatters()

const FUNDS = [
  { key: 'f101', label: 'F101', color: '#0D2137' },
  { key: 'f164', label: 'F164', color: '#C9A84C' },
  { key: 'f161', label: 'F161', color: '#1E8449' },
  { key: 'f163', label: 'F163', color: '#2E86C1' },
]
</script>

<template>
  <div class="grid grid-cols-3 gap-4">
    <div v-for="yr in [2024, 2025, 2026]" :key="yr"
      class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
      <div class="flex items-center justify-between mb-3">
        <span class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">FY {{ yr }}</span>
        <span class="text-[10px] font-bold text-gray-400 bg-gray-50 border border-gray-100 rounded-full px-2 py-0.5">
          {{ yearTotals[yr]?.dept_count ?? 0 }} offices
        </span>
      </div>
      <p class="text-[22px] font-extrabold text-[#0D2137] leading-none">
        {{ yearTotals[yr] ? phpM(yearTotals[yr].total) : '—' }}
      </p>
      <div class="mt-3 space-y-1.5">
        <div v-for="f in FUNDS" :key="f.key" class="flex items-center gap-2">
          <div class="w-1.5 h-1.5 rounded-full shrink-0" :style="{ background: f.color }" />
          <span class="text-[10px] text-gray-400 w-8">{{ f.label }}</span>
          <div class="flex-1 bg-gray-100 rounded-full h-1 overflow-hidden">
            <div class="h-full rounded-full"
              :style="{
                width: yearTotals[yr]?.total > 0
                  ? ((yearTotals[yr]?.[f.key] ?? 0) / yearTotals[yr].total * 100) + '%'
                  : '0%',
                background: f.color
              }" />
          </div>
          <span class="text-[10px] font-bold text-gray-500 w-10 text-right">
            {{ yearTotals[yr]?.total > 0
              ? ((yearTotals[yr]?.[f.key] ?? 0) / yearTotals[yr].total * 100).toFixed(1) + '%'
              : '—' }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
