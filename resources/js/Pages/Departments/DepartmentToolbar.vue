<script setup>
defineProps({
  years: { type: Array, default: () => [] },
  activeYear: { type: [Number, String, null], default: null },
})

defineEmits([
  'update:activeYear',
  'new-year',
  'delete-year',
  'add-department',
])
</script>

<template>
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div class="flex items-center gap-2 flex-wrap">
      <div class="flex gap-1 bg-gray-100 rounded-2xl p-1">
        <button
          v-for="year in years"
          :key="year"
          @click="$emit('update:activeYear', year)"
          :class="[
            'px-4 py-2 rounded-xl text-[13px] font-bold transition-all',
            activeYear === year
              ? 'bg-white text-[#064E3B] shadow-sm'
              : 'text-gray-400 hover:text-gray-600',
          ]"
        >
          {{ year }}
        </button>
      </div>

      <button
        @click="$emit('new-year')"
        class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-dashed border-gray-300 text-gray-400 hover:border-[#064E3B] hover:text-[#064E3B] transition-all text-[12px] font-bold"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path d="M12 5v14M5 12h14" />
        </svg>
        New Year
      </button>
    </div>

    <div class="flex items-center gap-2">
      <button
        v-if="activeYear"
        @click="$emit('delete-year')"
        class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-[12px] font-bold text-red-400 hover:text-red-600 hover:bg-red-50 border border-red-100 transition-all"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <polyline points="3 6 5 6 21 6" />
          <path d="M19 6l-1 14H6L5 6M10 11v6M14 11v6" />
        </svg>
        Delete FY {{ activeYear }}
      </button>

      <button
        @click="$emit('add-department')"
        class="flex items-center gap-2 px-4 py-2 rounded-xl bg-[#064E3B] text-white text-[13px] font-bold hover:bg-[#0F766E] transition-colors shadow-sm"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path d="M12 5v14M5 12h14" />
        </svg>
        Add Department
      </button>
    </div>
  </div>
</template>
