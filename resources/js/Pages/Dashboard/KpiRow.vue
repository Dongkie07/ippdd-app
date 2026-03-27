<script setup>
/**
 * Dashboard/KpiRow.vue
 * ─────────────────────────────────────────
 * The 4 KPI cards at the top of the dashboard:
 *   Total Budget Allocation
 *   Reporting Offices
 *   Average per Office
 *   Fund Mix breakdown
 *
 * Props:
 *   cur   Object — current year summary row
 *   year  Number — selected fiscal year
 */
import KpiCard from '@/Components/KpiCard.vue'
import { useFormatters } from '@/composables/useFormatters'
import { FUNDS } from '@/constants/wfp'

const props = defineProps({
  cur:  { type: Object,  default: () => ({}) },
  prev: { type: Object,  default: null },
  year: { type: Number,  required: true },
})

const { phpM } = useFormatters()

const trend = (field) => {
  if (!props.prev?.[field] || !props.cur?.[field]) return null
  return +((props.cur[field] - props.prev[field]) / props.prev[field] * 100).toFixed(1)
}

const fundPct = (key) => {
  const total = props.cur.total_budget ?? 0
  return total > 0 ? +((props.cur[key] ?? 0) / total * 100).toFixed(1) : 0
}

// Fund rows for the mini fund-mix card
// Maps FUNDS constant → { label, key (DB field mapped to yearSummary key), color }
const fundRows = [
  { label: 'Fund 101 — GAA',       key: 'total_101', color: '#0D2137' },
  { label: 'Fund 164 — Fiduciary', key: 'total_164', color: '#C9A84C' },
  { label: 'Fund 161',             key: 'total_161', color: '#1A5276' },
  { label: 'Fund 163',             key: 'total_163', color: '#1E8449' },
]
</script>

<template>
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

    <!-- Fund Mix mini-card -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 flex flex-col gap-2">
      <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">
        Fund Mix — FY {{ year }}
      </p>
      <div class="space-y-2 mt-1">
        <div v-for="f in fundRows" :key="f.key" class="flex items-center gap-2">
          <div class="w-2 h-2 rounded-full shrink-0" :style="{ background: f.color }" />
          <span class="text-[10px] text-gray-500 w-28 truncate">{{ f.label }}</span>
          <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
            <div class="h-full rounded-full transition-all"
              :style="{ width: fundPct(f.key) + '%', background: f.color }" />
          </div>
          <span class="text-[10px] font-bold text-gray-600 w-8 text-right">
            {{ fundPct(f.key) }}%
          </span>
        </div>
      </div>
    </div>

  </div>
</template>
