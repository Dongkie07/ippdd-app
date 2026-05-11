<script setup>
defineProps({
  eyebrow: { type: String, default: '' },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  tone: { type: String, default: 'green' },
})
</script>

<template>
  <section :class="[
    'page-hero relative overflow-hidden rounded-[1.75rem] p-6 text-white shadow-[0_24px_70px_rgba(6,78,59,0.24)]',
    tone === 'green' ? 'bg-gradient-to-br from-[#064E3B] via-[#075D3F] to-[#022C22]' : '',
    tone === 'gold' ? 'bg-gradient-to-br from-[#6B4E0E] via-[#987219] to-[#064E3B]' : '',
    tone === 'slate' ? 'bg-gradient-to-br from-[#102A24] via-[#064E3B] to-[#0F766E]' : '',
  ]">
    <div class="pointer-events-none absolute inset-0 opacity-35">
      <div class="absolute -left-20 -top-24 h-72 w-72 rounded-full border border-[#53D28C]/30" />
      <div class="absolute right-6 top-6 h-40 w-40 rounded-full bg-[#53D28C]/20 blur-3xl" />
      <div class="absolute -bottom-20 right-0 h-56 w-56 rounded-full border border-white/15" />
      <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,.12),rgba(255,255,255,0)_42%)]" />
    </div>

    <div class="relative grid gap-5 lg:grid-cols-[1.45fr_.85fr] lg:items-end">
      <div>
        <p v-if="eyebrow" class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[11px] font-black uppercase tracking-[0.16em] text-[#DDFBE8] backdrop-blur">
          <span class="h-2 w-2 rounded-full bg-[#53D28C] shadow-[0_0_18px_rgba(83,210,140,.9)]" />
          {{ eyebrow }}
        </p>
        <h2 class="max-w-4xl font-display text-2xl font-black leading-tight tracking-tight sm:text-3xl">
          {{ title }}
        </h2>
        <p v-if="subtitle" class="mt-3 max-w-3xl text-sm font-medium leading-6 text-[#DDFBE8]/85">
          {{ subtitle }}
        </p>
      </div>

      <div v-if="$slots.actions || $slots.stats" class="space-y-3">
        <slot name="actions" />
        <slot name="stats" />
      </div>
    </div>
  </section>
</template>
