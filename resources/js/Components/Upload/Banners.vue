<script setup>
defineProps({
  stage:        { type: String, default: 'idle' },
  savedMessage: { type: String, default: '' },
  error:        { type: String, default: null },
})
const emit = defineEmits(['reset'])
</script>

<template>
  <div class="space-y-3">
    <!-- Success -->
    <div v-if="stage === 'done'"
      class="flex items-center gap-4 bg-emerald-50 border border-emerald-200 rounded-2xl px-5 py-4">
      <div class="w-10 h-10 rounded-full bg-emerald-100 border-2 border-emerald-300 flex items-center justify-center shrink-0">
        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>
      <div class="flex-1">
        <p class="font-bold text-emerald-800 text-[13px]">Import Successful!</p>
        <p class="text-[12px] text-emerald-600 mt-0.5">{{ savedMessage }}</p>
      </div>
      <div class="flex gap-2">
        <a href="/" class="text-[12px] font-bold text-emerald-700 bg-emerald-100 hover:bg-emerald-200 transition-colors px-3 py-1.5 rounded-lg">
          View Dashboard →
        </a>
        <button @click="emit('reset')"
          class="text-[12px] font-bold text-emerald-600 hover:text-emerald-800 px-3 py-1.5 rounded-lg transition-colors">
          Upload Another
        </button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error"
      class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
      <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <p class="text-xs text-red-700 font-semibold">{{ error }}</p>
    </div>
  </div>
</template>
