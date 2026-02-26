<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  year: { type: Number, default: 2026 }
})

// ── State ─────────────────────────────────────────────────────
const isOpen      = ref(false)
const isStreaming = ref(false)
const isDone      = ref(false)
const error       = ref(null)
const rawText     = ref('')
let   eventSource = null

// ── Markdown → HTML (minimal renderer) ───────────────────────
const rendered = computed(() => {
  if (!rawText.value) return ''
  return rawText.value
    // Bold
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    // Headers
    .replace(/^### (.+)$/gm, '<h3 class="ai-h3">$1</h3>')
    .replace(/^## (.+)$/gm,  '<h2 class="ai-h2">$1</h2>')
    .replace(/^# (.+)$/gm,   '<h1 class="ai-h1">$1</h1>')
    // Bullet points
    .replace(/^[-•] (.+)$/gm, '<li class="ai-li">$1</li>')
    .replace(/(<li[\s\S]+?<\/li>)/g, '<ul class="ai-ul">$1</ul>')
    // Double ul wraps
    .replace(/<\/ul>\s*<ul class="ai-ul">/g, '')
    // Numbered list
    .replace(/^\d+\. (.+)$/gm, '<li class="ai-li ai-li--num">$1</li>')
    // Line breaks
    .replace(/\n\n/g, '</p><p class="ai-p">')
    .replace(/\n/g, '<br/>')
})

// ── Analyze ───────────────────────────────────────────────────
const analyze = () => {
  if (isStreaming.value) return
  rawText.value  = ''
  isDone.value   = false
  error.value    = null
  isStreaming.value = true

  if (eventSource) eventSource.close()

  eventSource = new EventSource(`/ai/analyze?year=${props.year}`)

  eventSource.onmessage = (e) => {
    if (e.data === '[DONE]') {
      isDone.value      = true
      isStreaming.value = false
      eventSource.close()
      return
    }
    try {
      const chunk = JSON.parse(e.data)
      if (chunk.text) rawText.value += chunk.text
    } catch { /* skip malformed chunks */ }
  }

  eventSource.onerror = () => {
    if (!isDone.value) {
      error.value = 'Connection interrupted. Please try again.'
    }
    isStreaming.value = false
    eventSource.close()
  }
}

// Re-analyze when year changes (only if already open)
watch(() => props.year, () => {
  if (isOpen.value && isDone.value) {
    rawText.value = ''
    isDone.value  = false
  }
})

const toggle = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value && !isDone.value && !isStreaming.value) {
    analyze()
  }
}

const retry = () => {
  error.value = null
  rawText.value = ''
  analyze()
}
</script>

<template>
  <!-- ── Panel wrapper ──────────────────────────────────────── -->
  <div class="rounded-2xl border overflow-hidden transition-all duration-300"
    :class="isOpen ? 'border-[#C9A84C]/40 shadow-lg shadow-[#C9A84C]/5' : 'border-gray-200'">

    <!-- ── Header / Toggle ──────────────────────────────────── -->
    <button @click="toggle"
      class="w-full flex items-center justify-between px-5 py-4 transition-all duration-200"
      :class="isOpen ? 'bg-gradient-to-r from-[#0D2137] to-[#1A3A5C]' : 'bg-white hover:bg-gray-50'">

      <div class="flex items-center gap-3">
        <!-- Gemini spark icon -->
        <div :class="['w-8 h-8 rounded-xl flex items-center justify-center transition-all',
          isOpen ? 'bg-[#C9A84C]/20 border border-[#C9A84C]/30' : 'bg-[#0D2137]/6 border border-[#0D2137]/10']">
          <svg class="w-4 h-4" :class="isOpen ? 'text-[#C9A84C]' : 'text-[#0D2137]'"
            viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2L9.5 9.5 2 12l7.5 2.5L12 22l2.5-7.5L22 12l-7.5-2.5L12 2z"/>
          </svg>
        </div>
        <div class="text-left">
          <p :class="['text-[13px] font-extrabold tracking-tight', isOpen ? 'text-white' : 'text-[#0D2137]']">
            AI Executive Insights
          </p>
          <p :class="['text-[11px] font-medium', isOpen ? 'text-white/50' : 'text-gray-400']">
            Powered by Google Gemini · FY {{ year }} analysis
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <!-- Streaming indicator -->
        <div v-if="isStreaming" class="flex items-center gap-1.5">
          <div class="flex gap-1">
            <span v-for="i in 3" :key="i"
              class="w-1.5 h-1.5 rounded-full bg-[#C9A84C] animate-bounce"
              :style="`animation-delay: ${(i-1)*0.15}s`" />
          </div>
          <span class="text-[10px] font-bold text-[#C9A84C]">Analyzing…</span>
        </div>

        <span v-if="isDone && !isStreaming"
          class="text-[10px] font-bold text-emerald-400 bg-emerald-400/10 border border-emerald-400/20 px-2 py-0.5 rounded-full">
          ✓ Complete
        </span>

        <!-- Chevron -->
        <svg :class="['w-4 h-4 transition-transform duration-300', isOpen ? 'rotate-180 text-white/60' : 'text-gray-400']"
          fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </div>
    </button>

    <!-- ── Body ───────────────────────────────────────────────── -->
    <Transition name="panel">
      <div v-if="isOpen" class="bg-white">

        <!-- Loading skeleton -->
        <div v-if="isStreaming && !rawText" class="px-6 py-5 space-y-3">
          <div class="flex items-center gap-2 mb-4">
            <div class="w-4 h-4 rounded-full bg-[#C9A84C]/20 border border-[#C9A84C]/30 flex items-center justify-center">
              <div class="w-2 h-2 rounded-full bg-[#C9A84C] animate-pulse" />
            </div>
            <span class="text-[11px] text-gray-400 font-medium">Gemini is reading FY {{ year }} WFP data…</span>
          </div>
          <div v-for="i in 4" :key="i" class="space-y-2">
            <div class="h-3 bg-gray-100 rounded-full animate-pulse" :style="`width: ${75 + Math.random()*20}%`" />
            <div class="h-3 bg-gray-100 rounded-full animate-pulse" :style="`width: ${55 + Math.random()*30}%`" />
            <div class="h-3 bg-gray-100 rounded-full animate-pulse" :style="`width: ${65 + Math.random()*20}%`" />
          </div>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="px-6 py-5">
          <div class="flex items-center gap-3 bg-red-50 border border-red-100 rounded-xl px-4 py-3">
            <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p class="text-[12px] text-red-700 font-medium flex-1">{{ error }}</p>
            <button @click="retry"
              class="text-[11px] font-bold text-red-600 hover:text-red-800 bg-red-100 hover:bg-red-200 px-3 py-1 rounded-lg transition-colors">
              Retry
            </button>
          </div>
        </div>

        <!-- Streamed content -->
        <div v-else-if="rawText" class="px-6 py-5">
          <!-- Streaming cursor indicator -->
          <div v-if="isStreaming" class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100">
            <div class="flex gap-1">
              <span v-for="i in 3" :key="i"
                class="w-1.5 h-1.5 rounded-full bg-[#C9A84C] animate-bounce"
                :style="`animation-delay: ${(i-1)*0.15}s`" />
            </div>
            <span class="text-[11px] text-gray-400 font-medium">Generating analysis…</span>
          </div>

          <!-- Rendered markdown content -->
          <div class="ai-content" v-html="rendered" />

          <!-- Footer actions -->
          <div v-if="isDone" class="flex items-center justify-between mt-5 pt-4 border-t border-gray-100">
            <div class="flex items-center gap-1.5 text-[11px] text-gray-400">
              <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2L9.5 9.5 2 12l7.5 2.5L12 22l2.5-7.5L22 12l-7.5-2.5L12 2z"/>
              </svg>
              Generated by Google Gemini · Based on FY {{ year }} WFP database
            </div>
            <button @click="retry"
              class="flex items-center gap-1.5 text-[11px] font-bold text-[#0D2137] hover:text-[#C9A84C] transition-colors">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/>
              </svg>
              Regenerate
            </button>
          </div>
        </div>

      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* Panel slide animation */
.panel-enter-active { transition: all 0.25s ease; }
.panel-leave-active { transition: all 0.2s ease; }
.panel-enter-from, .panel-leave-to { opacity: 0; transform: translateY(-6px); }

/* AI content typography */
.ai-content { font-size: 13px; line-height: 1.7; color: #374151; }

:deep(.ai-h1) { font-size: 15px; font-weight: 800; color: #0D2137; margin: 16px 0 8px; }
:deep(.ai-h2) { font-size: 14px; font-weight: 800; color: #0D2137; margin: 14px 0 6px; padding-bottom: 4px; border-bottom: 2px solid #F3F4F6; }
:deep(.ai-h3) { font-size: 13px; font-weight: 700; color: #1A5276; margin: 12px 0 4px; }
:deep(.ai-p)  { margin: 8px 0; }

:deep(.ai-ul) {
  list-style: none;
  padding: 0;
  margin: 6px 0 10px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
:deep(.ai-li) {
  display: flex;
  gap: 8px;
  padding: 6px 10px;
  background: #F8F9FA;
  border-left: 3px solid #C9A84C;
  border-radius: 0 8px 8px 0;
  font-size: 12px;
  line-height: 1.6;
  color: #4B5563;
}
:deep(.ai-li)::before {
  content: '▸';
  color: #C9A84C;
  font-size: 10px;
  margin-top: 2px;
  shrink: 0;
}
:deep(.ai-li--num) {
  border-left-color: #0D2137;
}
:deep(strong) {
  font-weight: 700;
  color: #0D2137;
}
</style>
