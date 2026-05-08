<script setup>
defineProps({
  title:    { type: String, default: '' },
  subtitle: { type: String, default: '' },
  noPad:    { type: Boolean, default: false },
})
</script>

<template>
  <section class="relative overflow-hidden rounded-[1.35rem] border border-[#DDEDE3] bg-white/95 shadow-[0_14px_40px_rgba(6,78,59,0.08)] backdrop-blur-sm">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#064E3B] via-[#168A4A] to-[#53D28C]" />
    <div v-if="title || subtitle || $slots.actions" class="flex items-center justify-between gap-4 border-b border-[#E6F2EA] bg-gradient-to-r from-[#F8FCF9] to-white px-6 py-4">
      <div class="min-w-0">
        <h3 v-if="title" class="truncate text-[14px] font-display font-extrabold tracking-tight text-[#064E3B]">
          {{ title }}
        </h3>
        <p v-if="subtitle" class="mt-0.5 text-[12px] font-medium text-[#64746B]">
          {{ subtitle }}
        </p>
      </div>
      <div v-if="$slots.actions" class="shrink-0">
        <slot name="actions" />
      </div>
    </div>
    <div :class="noPad ? '' : 'p-6'">
      <slot />
    </div>
  </section>
</template>
