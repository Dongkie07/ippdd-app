<script setup>
defineProps({
  item: { type: Object, default: null },
  recordCount: { type: Number, default: 0 },
  saving: { type: Boolean, default: false },
})

defineEmits(['cancel', 'confirm'])
</script>

<template>
  <Transition name="modal">
    <div v-if="item" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-8">
        <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M12 9v4M12 17h.01" />
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
          </svg>
        </div>

        <h3 class="text-[15px] font-extrabold text-[#064E3B] text-center mb-2">Confirm Delete</h3>

        <p class="text-[12px] text-gray-500 text-center leading-relaxed">
          <template v-if="item.year">
            This will permanently delete <strong>all data for FY {{ item.year }}</strong>
            ({{ recordCount }} records). This cannot be undone.
          </template>
          <template v-else>
            Delete <strong>"{{ item.name }}"</strong> and all its sub-offices? This cannot be undone.
          </template>
        </p>

        <div class="flex gap-3 mt-6">
          <button
            @click="$emit('cancel')"
            class="flex-1 py-2.5 rounded-xl border border-gray-200 text-[13px] font-semibold text-gray-500"
          >
            Cancel
          </button>

          <button
            @click="$emit('confirm')"
            :disabled="saving"
            class="flex-1 py-2.5 rounded-xl bg-red-500 text-white text-[13px] font-bold hover:bg-red-600 transition-colors disabled:opacity-40"
          >
            {{ saving ? 'Deleting…' : 'Yes, Delete' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
