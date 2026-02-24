<script setup>
const props = defineProps({
  modelValue: { type: Number, required: true },
  years:      { type: Array,  default: () => [2024, 2025, 2026] },
})
const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div class="inline-flex items-center gap-1 bg-gray-100 rounded-xl p-1">
    <button
      v-for="y in years"
      :key="y"
      @click="emit('update:modelValue', y)"
      :class="[
        'px-4 py-1.5 rounded-lg text-sm font-semibold transition-all duration-150',
        modelValue === y
          ? 'bg-white text-[#0B1F3A] shadow-sm'
          : 'text-gray-400 hover:text-gray-700'
      ]"
    >{{ y }}</button>
  </div>
</template>