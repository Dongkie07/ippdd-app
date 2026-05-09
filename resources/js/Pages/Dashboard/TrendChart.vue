<script setup>
/**
 * Dashboard/TrendChart.vue
 * Dynamic budget trend line chart.
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
    title="Budget Trend Intelligence"
    subtitle="Total budget · Fund 101 (GAA) · Fund 164 (Fiduciary) · Fund 161 · Fund 163">
    <div class="h-[230px]">
      <Line :data="lineData" :options="lineOpts" />
    </div>
  </SectionCard>
</template>
