<script setup>
/**
 * Pages/Budget.vue — Department Breakdown
 * Thin page only. Table shaping and chart data live in Budget/useBudgetBreakdown.js.
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
import BudgetTrendChart from './Budget/BudgetTrendChart.vue'
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
  tabs,
  yearsRef,
  latestYearRef,
  previousYearRef,
  yearTotalsRef,
  yoyRowsRef,
  yoyChangeKey,
  trendData,
  trendOpts,
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
  search,
} = useBudgetBreakdown(props)
</script>

<template>
  <AppLayout>
    <template #breadcrumb>Department Breakdown</template>
    <template #title>Department Breakdown</template>
    <template #subtitle>
      Budget analysis across all departments · FY {{ yearsRef[0] ?? '—' }}–{{ latestYearRef ?? '—' }}
    </template>

    <div class="space-y-5">
      <YearSummaryCards :year-totals="yearTotalsRef" :years="yearsRef" />

      <BudgetTrendChart
        :chart-data="trendData"
        :chart-options="trendOpts"
        :first-year="yearsRef[0]"
        :latest-year="latestYearRef"
      />

      <BudgetTabs v-model:activeTab="activeTab" :tabs="tabs" />

      <RankingTab
        v-if="activeTab === 'ranking'"
        :filtered-tree="filteredTree"
        :expanded-rows="expandedRows"
        :parent-rows="parentRows"
        :years="yearsRef"
        :latest-year="latestYearRef"
        :previous-year="previousYearRef"
        :change-field="yoyChangeKey"
        @toggle="toggleExpand"
        @expand-all="expandAll"
        @collapse-all="collapseAll"
        @update:search="search = $event"
      />

      <FundMixTab
        v-if="activeTab === 'fundmix'"
        :rows="fundRows"
        :years="yearsRef"
        :latest-year="latestYearRef"
      />

      <YearComparisonTab
        v-if="activeTab === 'yoy'"
        :rows="yoyRowsRef"
        :years="yearsRef"
        :latest-year="latestYearRef"
        :chart-data="yoyChartData"
        :chart-options="yoyChartOpts"
        :trend-color="trendColor"
        :trend-bg="trendBg"
      />
    </div>
  </AppLayout>
</template>
