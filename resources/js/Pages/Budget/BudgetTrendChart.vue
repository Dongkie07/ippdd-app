<script setup>
import { Line } from 'vue-chartjs'
import SectionCard from '@/Components/SectionCard.vue'

defineProps({
  chartData: { type: Object, required: true },
  chartOptions: { type: Object, required: true },
  firstYear: { type: [Number, String, null], default: null },
  latestYear: { type: [Number, String, null], default: null },
})
</script>

<template>
  <SectionCard
    title="Budget Trend"
    :subtitle="`Total budget · Fund 101 · Fund 164 across FY ${firstYear ?? '—'}–${latestYear ?? '—'}`"
  >
    <div class="h-[180px]">
      <Line :data="chartData" :options="chartOptions" />
    </div>
  </SectionCard>
</template>
