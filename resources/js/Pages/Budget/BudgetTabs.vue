<script setup>
defineProps({
  tabs: { type: Array, default: () => [] },
  activeTab: { type: String, required: true },
})

defineEmits(['update:activeTab'])
</script>

<template>
  <div class="inline-flex flex-wrap items-center gap-1 rounded-[1.25rem] border border-[#DDEDE3] bg-white/90 p-1.5 shadow-[0_12px_32px_rgba(6,78,59,0.08)] backdrop-blur">
    <button
      v-for="tab in tabs"
      :key="tab.id"
      @click="$emit('update:activeTab', tab.id)"
      :class="[
        'relative rounded-2xl px-4 py-2.5 text-[12px] font-black transition-all',
        activeTab === tab.id
          ? 'bg-[#064E3B] text-white shadow-lg shadow-[#064E3B]/20'
          : 'text-[#64746B] hover:-translate-y-0.5 hover:bg-[#ECFDF3] hover:text-[#064E3B]',
      ]"
    >
      {{ tab.label }}
      <span v-if="activeTab === tab.id" class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-[#53D28C] ring-2 ring-white" />
    </button>
  </div>
</template>
