<script setup>
defineProps({
  file:     { type: Object, default: null },
  dragging: { type: Boolean, default: false },
  year:     { type: Number, required: true },
  stage:    { type: String, default: 'idle' },
})
const emit = defineEmits(['pick', 'clear', 'upload', 'dragging'])
</script>

<template>
  <div>
    <!-- Drop zone -->
    <div
      @dragover.prevent="emit('dragging', true)"
      @dragleave="emit('dragging', false)"
      @drop.prevent="e => { emit('dragging', false); emit('pick', e) }"
      @click="$refs.fi.click()"
      :class="[
        'border-2 border-dashed rounded-2xl py-14 text-center cursor-pointer transition-all select-none',
        file
          ? 'border-[#0D2137]/30 bg-[#0D2137]/[0.025]'
          : dragging
            ? 'border-[#C9A84C] bg-[#C9A84C]/5 scale-[1.01]'
            : 'border-gray-200 hover:border-[#0D2137]/40 hover:bg-gray-50/80'
      ]">
      <input ref="fi" type="file" accept=".xlsx,.xls" class="hidden" @change="e => emit('pick', e)" />

      <!-- Empty state -->
      <template v-if="!file">
        <div class="w-14 h-14 rounded-2xl bg-[#0D2137]/6 border border-[#0D2137]/10 flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-[#0D2137]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4-4 4M12 4v12"/>
          </svg>
        </div>
        <p class="font-bold text-gray-600">Drop your WFP Excel file here</p>
        <p class="text-[12px] text-gray-400 mt-1">or click to browse &nbsp;·&nbsp; .xlsx or .xls only</p>
      </template>

      <!-- File selected -->
      <template v-else>
        <div class="flex items-center justify-center gap-4 px-8">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
            </svg>
          </div>
          <div class="text-left">
            <p class="font-bold text-[#0D2137] text-[13px]">{{ file.name }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">{{ (file.size / 1024).toFixed(0) }} KB &nbsp;·&nbsp; FY {{ year }}</p>
          </div>
          <button @click.stop="emit('clear')"
            class="ml-2 text-gray-300 hover:text-red-400 transition-colors p-1.5 rounded-lg hover:bg-red-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </template>
    </div>

    <!-- Upload button -->
    <button v-if="file"
      @click="emit('upload')"
      :disabled="stage === 'uploading'"
      class="mt-4 w-full py-3 rounded-xl bg-[#0D2137] text-white font-bold text-[13px] tracking-wide
             hover:bg-[#1A5276] transition-colors disabled:opacity-60
             flex items-center justify-center gap-2.5 shadow-sm">
      <svg v-if="stage === 'uploading'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
      </svg>
      {{ stage === 'uploading' ? 'Reading Excel file…' : `Parse & Preview FY ${year} Data` }}
    </button>
  </div>
</template>
