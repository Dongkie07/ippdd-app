<script setup>
/**
 * Pages/Budget.vue — Department Breakdown
 * Thin page component only. Data shaping lives in composables/budget/useBudgetBreakdown.js,
 * while each tab owns its own table/chart UI.
 */
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/PageHero.vue'
import MiniMetric from '@/Components/MiniMetric.vue'
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
import { useBudgetBreakdown } from '@/composables/budget/useBudgetBreakdown'
import { useFormatters } from '@/composables/useFormatters'

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

const { phpM } = useFormatters()
const latestTotal = computed(() => latestYearRef.value ? (yearTotalsRef.value[latestYearRef.value] ?? 0) : 0)
const officeCount = computed(() => parentRows.value.length)
const trackedYearsLabel = computed(() => yearsRef.value.length ? `FY ${yearsRef.value[0]}–${yearsRef.value.at(-1)}` : 'No years yet')
</script>

<template>
  <AppLayout>
    <template #breadcrumb />
    <template #title>Department Breakdown</template>
    <template #subtitle>
      Budget analysis across all departments · FY {{ yearsRef[0] ?? '—' }}–{{ latestYearRef ?? '—' }}
    </template>

    <div class="space-y-5">
      <PageHero
        eyebrow="Budget Intelligence"
        title="Interactive department breakdown with cleaner comparisons"
        subtitle="Review ranking, fund mix, and year-over-year movement from one page. It groups renamed offices by stable identity, because graphs should not panic every time an office gets a new name."
      >
        <template #stats>
          <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Latest Year</p>
              <p class="mt-1 text-2xl font-black text-white">FY {{ latestYearRef ?? '—' }}</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Latest Budget</p>
              <p class="mt-1 text-2xl font-black text-white">{{ phpM(latestTotal) }}</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Tracked Offices</p>
              <p class="mt-1 text-2xl font-black text-white">{{ officeCount }}</p>
            </div>
          </div>
        </template>
      </PageHero>

      <div class="grid gap-4 md:grid-cols-3">
        <MiniMetric label="Tracked Range" :value="trackedYearsLabel" note="Available fiscal years" />
        <MiniMetric label="Active Tab" :value="tabs.find((tab) => tab.id === activeTab)?.label ?? 'Budget Ranking'" note="Current analysis mode" />
        <MiniMetric label="Matched Rows" :value="filteredTree.length" note="Visible in current filters" />
      </div>

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
