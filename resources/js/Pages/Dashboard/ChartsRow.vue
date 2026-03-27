<script setup>
/**
 * Dashboard/ChartsRow.vue
 * ─────────────────────────────────────────
 * Top 10 bar chart + Budget Distribution donut chart,
 * side by side.
 *
 * Props:
 *   barData    Object — Chart.js dataset for bar chart
 *   barOpts    Object — Chart.js options for bar chart
 *   donutData  Object — Chart.js dataset for donut chart
 *   donutOpts  Object — Chart.js options for donut chart
 *   year       Number — selected fiscal year (for card title)
 */
import SectionCard from '@/Components/SectionCard.vue'
import { Bar, Doughnut } from 'vue-chartjs'

const props = defineProps({
  barData:   { type: Object, required: true },
  barOpts:   { type: Object, required: true },
  donutData: { type: Object, required: true },
  donutOpts: { type: Object, required: true },
  year:      { type: Number, required: true },
})
</script>

<template>
  <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <SectionCard
      class="xl:col-span-2"
      :title="`Top 10 Offices — FY ${year}`"
      subtitle="Ranked by total budget allocation">
      <div class="h-[260px]">
        <Bar :data="barData" :options="barOpts" />
      </div>
    </SectionCard>

    <SectionCard title="Budget Distribution" subtitle="Top 6 offices by share">
      <div class="h-[260px]">
        <Doughnut :data="donutData" :options="donutOpts" />
      </div>
    </SectionCard>
  </div>
</template>
