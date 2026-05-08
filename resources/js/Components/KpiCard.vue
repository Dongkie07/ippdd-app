<script setup>
import { computed } from 'vue'

const props = defineProps({
  label:    { type: String,  required: true },
  value:    { type: String,  required: true },
  sub:      { type: String,  default: '' },
  trend:    { type: Number,  default: null },   // positive = up, negative = down
  color:    { type: String,  default: 'green' }, // dark | green | mint | teal | amber
  accent:   { type: String,  default: '' },      // backwards compatible alias
  icon:     { type: String,  default: 'peso' },  // peso | target | building | chart
})

const tone = computed(() => props.accent || props.color)
</script>

<template>
  <article class="group relative overflow-hidden rounded-[1.35rem] border border-[#DDEDE3] bg-white p-5 shadow-[0_14px_36px_rgba(6,78,59,0.08)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_18px_44px_rgba(6,78,59,0.14)]">
    <div :class="[
      'absolute inset-x-0 top-0 h-1 bg-gradient-to-r',
      tone === 'dark'  || tone === 'navy' ? 'from-[#043927] via-[#064E3B] to-[#168A4A]' : '',
      tone === 'green' ? 'from-[#064E3B] via-[#168A4A] to-[#53D28C]' : '',
      tone === 'mint'  ? 'from-[#168A4A] via-[#53D28C] to-[#B7F4CE]' : '',
      tone === 'teal'  ? 'from-[#0F766E] via-[#139A78] to-[#53D28C]' : '',
      tone === 'amber' ? 'from-[#D6B74A] via-[#F1D46B] to-[#53D28C]' : '',
    ]" />

    <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-[#53D28C]/10 transition-transform duration-300 group-hover:scale-125" />

    <div class="relative flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#8FA79B]">{{ label }}</p>
        <p class="mt-2 truncate text-2xl font-display font-black leading-none tracking-tight text-[#064E3B]">{{ value }}</p>

        <div class="mt-3 flex flex-wrap items-center gap-2">
          <span v-if="trend !== null" :class="[
            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-extrabold',
            trend >= 0 ? 'bg-[#ECFDF3] text-[#168A4A]' : 'bg-rose-50 text-rose-500'
          ]">
            {{ trend >= 0 ? '▲' : '▼' }} {{ Math.abs(trend) }}%
          </span>
          <span v-if="sub" class="text-[12px] font-semibold text-[#64746B]">{{ sub }}</span>
        </div>
      </div>

      <div :class="[
        'relative flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border shadow-sm',
        tone === 'dark'  || tone === 'navy' ? 'border-[#064E3B]/10 bg-[#064E3B]/[0.08] text-[#064E3B]' : '',
        tone === 'green' ? 'border-[#168A4A]/10 bg-[#ECFDF3] text-[#168A4A]' : '',
        tone === 'mint'  ? 'border-[#53D28C]/20 bg-[#53D28C]/[0.12] text-[#168A4A]' : '',
        tone === 'teal'  ? 'border-[#0F766E]/15 bg-[#0F766E]/[0.08] text-[#0F766E]' : '',
        tone === 'amber' ? 'border-[#D6B74A]/20 bg-[#FFF8DB] text-[#8B6A0A]' : '',
      ]">
        <svg v-if="icon === 'peso'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M6 3h8a4 4 0 010 8H6M6 11h8M6 3v18M6 7h10"/>
        </svg>
        <svg v-if="icon === 'target'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1" fill="currentColor"/>
        </svg>
        <svg v-if="icon === 'building'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
          <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
        </svg>
        <svg v-if="icon === 'chart'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M3 18V10M8 18V6M13 18v-4M18 18V8"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </div>
    </div>
  </article>
</template>
