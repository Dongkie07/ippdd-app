<script setup>
defineProps({
  title:    { type: String, default: '' },
  subtitle: { type: String, default: '' },
  noPad:    { type: Boolean, default: false },
})
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div v-if="title || subtitle || $slots.actions" class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <div>
        <h3 v-if="title" class="text-sm font-display font-semibold text-gray-700">{{ title }}</h3>
        <p v-if="subtitle" class="text-xs text-gray-400 mt-0.5">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.actions">
        <slot name="actions" />
      </div>
    </div>
    <div :class="noPad ? '' : 'p-6'">
      <slot />
    </div>
  </div>
</template>