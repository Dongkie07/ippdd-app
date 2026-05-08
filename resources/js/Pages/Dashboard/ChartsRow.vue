<script setup>
/**
 * Dashboard/ChartsRow.vue
 * Top 10 bar chart + Budget Distribution donut chart.
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
  <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
    <SectionCard
      class="xl:col-span-2"
      :title="`Top 10 Offices — FY ${year}`"
      subtitle="Same DNSC-green palette as the distribution chart, because charts should not dress like strangers.">
      <div class="h-[300px]">
        <Bar :data="barData" :options="barOpts" />
      </div>
    </SectionCard>

    <SectionCard title="Budget Distribution" subtitle="Top 10 offices by total share">
      <div class="h-[300px]">
        <Doughnut :data="donutData" :options="donutOpts" />
      </div>
    </SectionCard>
  </div>
</template>
