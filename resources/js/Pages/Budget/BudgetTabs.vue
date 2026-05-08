<script setup>
defineProps({
  tabs: { type: Array, default: () => [] },
  activeTab: { type: String, required: true },
})

defineEmits(['update:activeTab'])
</script>

<template>
  <div class="flex items-center gap-1 bg-gray-100 rounded-2xl p-1 w-fit">
    <button
      v-for="tab in tabs"
      :key="tab.id"
      @click="$emit('update:activeTab', tab.id)"
      :class="[
        'px-4 py-2 rounded-xl text-[12px] font-bold transition-all',
        activeTab === tab.id ? 'bg-white text-[#064E3B] shadow-sm' : 'text-gray-400 hover:text-gray-600',
      ]"
    >
      {{ tab.label }}
    </button>
  </div>
</template>
