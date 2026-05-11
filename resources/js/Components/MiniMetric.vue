<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  note: { type: String, default: '' },
})
</script>

<template>
  <div class="interactive-card rounded-2xl border border-[#DDEDE3] bg-white/95 p-4 shadow-[0_12px_32px_rgba(6,78,59,0.08)]">
    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#8FA79B]">{{ label }}</p>
    <p class="mt-1 truncate text-xl font-black tracking-tight text-[#064E3B]">{{ value }}</p>
    <p v-if="note" class="mt-1 text-[11px] font-semibold text-[#64746B]">{{ note }}</p>
  </div>
</template>
