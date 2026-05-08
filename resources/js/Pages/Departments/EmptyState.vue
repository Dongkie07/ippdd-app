<script setup>
defineProps({
  activeYear: { type: [Number, String, null], default: null },
})

defineEmits(['create-year', 'add-department'])
</script>

<template>
  <div class="bg-white rounded-2xl border-2 border-dashed border-gray-200 py-20 text-center">
    <template v-if="!activeYear">
      <p class="text-gray-300 text-sm mb-3">No fiscal years yet</p>
      <button
        @click="$emit('create-year')"
        class="px-4 py-2 rounded-xl bg-[#064E3B] text-white text-[13px] font-bold"
      >
        Create First Year
      </button>
    </template>

    <template v-else>
      <p class="text-gray-300 text-sm mb-3">No departments for FY {{ activeYear }} yet.</p>
      <button
        @click="$emit('add-department')"
        class="px-4 py-2 rounded-xl bg-[#064E3B] text-white text-[12px] font-bold hover:bg-[#0F766E] transition-colors"
      >
        + Add First Department
      </button>
    </template>
  </div>
</template>
