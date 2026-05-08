<script setup>
defineProps({
  feedback: { type: Object, default: null },
})
</script>

<template>
  <Transition name="toast">
    <div v-if="feedback" :class="[
      'fixed top-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5',
      'rounded-2xl shadow-xl border text-[13px] font-semibold max-w-sm',
      feedback.type === 'success'
        ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
        : 'bg-red-50 border-red-200 text-red-800',
    ]">
      <span class="text-lg">{{ feedback.type === 'success' ? '✓' : '✕' }}</span>
      <span>{{ feedback.message }}</span>
    </div>
  </Transition>
</template>
