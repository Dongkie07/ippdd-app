<script setup>
/**
 * Pages/Budget.vue — Department Breakdown
 * Thin page component only. Data shaping lives in useBudgetBreakdown.js,
 * while each tab owns its own table/chart UI.
 */
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'

import BudgetTabs from './Budget/BudgetTabs.vue'
import FundMixTab from './Budget/FundMixTab.vue'
import RankingTab from './Budget/RankingTab.vue'
import YearComparisonTab from './Budget/YearComparisonTab.vue'
import YearSummaryCards from './Budget/YearSummaryCards.vue'
import { useBudgetBreakdown } from './Budget/useBudgetBreakdown'

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend)

const props = defineProps({
  years: { type: Array, default: () => [] },
  allByYear: { type: Object, default: () => ({}) },
  yearTotals: { type: Object, default: () => ({}) },
  yoyRows: { type: Array, default: () => [] },
  gainers: { type: Array, default: () => [] },
  losers: { type: Array, default: () => [] },
  fundMix: { type: Array, default: () => [] },
})
const {
  activeTab,
  search,
  tabs,
  yearsRef,
  latestYearRef,
  previousYearRef,
  yearTotalsRef,
  yoyRowsRef,
  yoyChangeKey,
  yoyChartData,
  yoyChartOpts,
  parentRows,
  filteredTree,
  fundRows,
  expandedRows,
  toggleExpand,
  expandAll,
  collapseAll,
  trendColor,
  trendBg,
} = useBudgetBreakdown(props)

</script>

<template>
  <AppLayout>
    <template #breadcrumb />
    <template #title>Department Breakdown</template>
    <template #subtitle>
      Budget analysis across all departments · FY {{ yearsRef[0] ?? '—' }}–{{ latestYearRef ?? '—' }}
    </template>

    <div class="space-y-5">
      <YearSummaryCards :yearTotals="yearTotalsRef" :years="yearsRef" />

      

      <BudgetTabs v-model:activeTab="activeTab" :tabs="tabs" />

      <RankingTab
        v-if="activeTab === 'ranking'"
        :filteredTree="filteredTree"
        :expandedRows="expandedRows"
        :parentRows="parentRows"
        :years="yearsRef"
        :latestYear="latestYearRef"
        :previousYear="previousYearRef"
        :changeField="yoyChangeKey"
        @toggle="toggleExpand"
        @expand-all="expandAll"
        @collapse-all="collapseAll"
        @update:search="search = $event"
      />

      <FundMixTab
        v-if="activeTab === 'fundmix'"
        :rows="fundRows"
        :years="yearsRef"
        :latestYear="latestYearRef"
      />

      <YearComparisonTab
        v-if="activeTab === 'yoy'"
        :rows="yoyRowsRef"
        :years="yearsRef"
        :latestYear="latestYearRef"
        :chartData="yoyChartData"
        :chartOptions="yoyChartOpts"
        :trendColor="trendColor"
        :trendBg="trendBg"
      />
    </div>
  </AppLayout>
</template>
