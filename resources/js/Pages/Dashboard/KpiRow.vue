<script setup>
/**
 * Dashboard/KpiRow.vue
 * The 4 KPI cards at the top of the dashboard.
 */
import KpiCard from '@/Components/KpiCard.vue'
import { useFormatters } from '@/composables/useFormatters'

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

const fundRows = [
  { label: 'Fund 101 — GAA',       key: 'total_101', color: '#064E3B' },
  { label: 'Fund 164 — Fiduciary', key: 'total_164', color: '#D6B74A' },
  { label: 'Fund 161',             key: 'total_161', color: '#168A4A' },
  { label: 'Fund 163',             key: 'total_163', color: '#0F766E' },
]
</script>

<template>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <KpiCard
      label="Total Budget Allocation"
      :value="phpM(cur.total_budget ?? 0)"
      :trend="trend('total_budget')"
      :sub="trend('total_budget') !== null ? `vs FY ${year - 1}` : ''"
      color="dark" icon="peso" />

    <KpiCard
      label="Reporting Offices"
      :value="(cur.dept_count ?? 0).toString()"
      :sub="`offices in FY ${year}`"
      color="green" icon="building" />

    <KpiCard
      label="Average per Office"
      :value="cur.dept_count ? phpM((cur.total_budget ?? 0) / cur.dept_count) : '₱0'"
      sub="budget per office"
      color="teal" icon="chart" />

    <!-- Fund Mix mini-card -->
    <div class="relative overflow-hidden rounded-[1.35rem] border border-[#DDEDE3] bg-white p-5 shadow-[0_14px_36px_rgba(6,78,59,0.08)]">
      <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#064E3B] via-[#D6B74A] to-[#0F766E]" />
      <p class="text-[11px] font-black uppercase tracking-[0.16em] text-[#8FA79B]">
        Fund Mix — FY {{ year }}
      </p>
      <div class="mt-4 space-y-3">
        <div v-for="f in fundRows" :key="f.key" class="grid grid-cols-[.5rem_1fr_3rem] items-center gap-2">
          <div class="h-2 w-2 rounded-full" :style="{ background: f.color }" />
          <div class="min-w-0">
            <div class="mb-1 flex items-center justify-between gap-2">
              <span class="truncate text-[10px] font-bold text-[#64746B]">{{ f.label }}</span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-[#E6F2EA]">
              <div class="h-full rounded-full transition-all duration-500"
                :style="{ width: fundPct(f.key) + '%', background: f.color }" />
            </div>
          </div>
          <span class="text-right text-[10px] font-black text-[#064E3B]">
            {{ fundPct(f.key) }}%
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
