<script setup>
/**
 * Dashboard/TrendChart.vue
 * ─────────────────────────────────────────
 * 3-Year Budget Trend line chart.
 *
 * Props:
 *   lineData  Object — Chart.js dataset
 *   lineOpts  Object — Chart.js options
 */
import SectionCard from '@/Components/SectionCard.vue'
import { Line } from 'vue-chartjs'

defineProps({
  lineData: { type: Object, required: true },
  lineOpts: { type: Object, required: true },
})
</script>

<template>
  <SectionCard
    title="3-Year Budget Trend"
    subtitle="Total budget · Fund 101 (GAA) · Fund 164 (Fiduciary)">
    <div class="h-[200px]">
      <Line :data="lineData" :options="lineOpts" />
    </div>
  </SectionCard>
</template>
