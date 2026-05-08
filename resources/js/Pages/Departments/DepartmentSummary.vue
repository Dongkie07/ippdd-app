<script setup>
import { useFormatters } from '@/composables/useFormatters'

defineProps({
  activeYear: { type: [Number, String], required: true },
  totalBudget: { type: Number, default: 0 },
  fundTotals: { type: Array, default: () => [] },
  topLevelCount: { type: Number, default: 0 },
})

const { php, phpM } = useFormatters()
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-6 py-4 flex items-center justify-between flex-wrap gap-4">
    <div>
      <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">
        FY {{ activeYear }} — Total Budget
      </p>
      <p class="text-[24px] font-extrabold text-[#064E3B] mt-0.5">{{ php(totalBudget) }}</p>
    </div>

    <div class="flex items-center gap-5 flex-wrap">
      <div v-for="fund in fundTotals" :key="fund.key" class="text-right">
        <p class="text-[9px] font-extrabold uppercase tracking-widest" :style="{ color: fund.color }">
          {{ fund.label }}
        </p>
        <p class="text-[13px] font-bold text-[#064E3B]">{{ phpM(fund.value) }}</p>
      </div>

      <div class="border-l border-gray-100 pl-5 text-right">
        <p class="text-[9px] font-extrabold uppercase tracking-widest text-gray-400">Top-level</p>
        <p class="text-[13px] font-bold text-[#064E3B]">{{ topLevelCount }} offices</p>
      </div>
    </div>
  </div>
</template>
