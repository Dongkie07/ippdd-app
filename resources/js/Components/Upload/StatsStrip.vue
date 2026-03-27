<script setup>
defineProps({
  parseStats:  { type: Object, required: true },
  previewRows: { type: Array,  required: true },
  selectedRows:{ type: Array,  required: true },
  totalBudget: { type: Number, required: true },
  phpM:        { type: Function, required: true },
})
</script>

<template>
  <div class="grid grid-cols-4 divide-x divide-gray-100 border-b border-gray-100 shrink-0">
    <div class="px-6 py-3.5 text-center">
      <p class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-gray-400">Fiscal Year</p>
      <p class="text-[18px] font-extrabold text-[#0D2137] mt-0.5">FY {{ parseStats.year }}</p>
    </div>
    <div class="px-6 py-3.5 text-center">
      <p class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-gray-400">Detected</p>
      <p class="text-[18px] font-extrabold text-[#0D2137] mt-0.5">
        {{ previewRows.length }} <span class="text-xs text-gray-400 font-medium">depts</span>
      </p>
    </div>
    <div class="px-6 py-3.5 text-center">
      <p class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-gray-400">Selected</p>
      <p class="text-[18px] font-extrabold text-[#0D2137] mt-0.5">
        {{ selectedRows.length }}
        <span class="text-xs text-gray-400 font-medium">/ {{ previewRows.length }}</span>
      </p>
    </div>
    <div class="px-6 py-3.5 text-center">
      <p class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-gray-400">Total Budget</p>
      <p class="text-[18px] font-extrabold text-[#0D2137] mt-0.5">{{ phpM(totalBudget) }}</p>
    </div>
  </div>
</template>
