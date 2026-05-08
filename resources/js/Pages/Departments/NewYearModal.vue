<script setup>
defineProps({
  show: { type: Boolean, default: false },
  form: { type: Object, required: true },
  years: { type: Array, default: () => [] },
  deptsByYear: { type: Object, default: () => ({}) },
  saving: { type: Boolean, default: false },
})

defineEmits(['close', 'submit'])
</script>

<template>
  <Transition name="modal">
    <div v-if="show" class="fixed inset-0 z-40 flex items-center justify-center bg-black/30 backdrop-blur-sm p-4">
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm">
        <div class="px-7 pt-6 pb-4 border-b border-gray-100">
          <h2 class="text-[16px] font-extrabold text-[#064E3B]">Create Fiscal Year</h2>
          <p class="text-[11px] text-gray-400 mt-0.5">Blank year or copy structure from an existing one</p>
        </div>

        <div class="px-7 py-5 space-y-4">
          <div>
            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Fiscal Year *</label>
            <input
              v-model.number="form.year"
              type="number"
              min="2024"
              max="2099"
              class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064E3B]/20 bg-white text-center text-[18px] font-extrabold text-[#064E3B]"
            />
          </div>

          <div>
            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">
              Copy Departments From <span class="text-gray-300 font-normal">(optional)</span>
            </label>
            <select
              v-model="form.copy_from"
              class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064E3B]/20 text-gray-700 bg-white"
            >
              <option value="">— Start blank —</option>
              <option v-for="year in years" :key="year" :value="year">FY {{ year }}</option>
            </select>
            <p class="text-[10px] text-gray-400 mt-1.5 leading-relaxed">
              {{ form.copy_from
                ? `Copies all ${(deptsByYear[form.copy_from] ?? []).length} departments from FY ${form.copy_from} with ₱0 budgets`
                : 'Creates an empty year — add departments manually or upload a WFP file' }}
            </p>
          </div>
        </div>

        <div class="px-7 pb-6 flex gap-3">
          <button
            @click="$emit('close')"
            class="flex-1 py-2.5 rounded-xl border border-gray-200 text-[13px] font-semibold text-gray-500"
          >
            Cancel
          </button>

          <button
            @click="$emit('submit')"
            :disabled="saving || !form.year"
            class="flex-1 py-2.5 rounded-xl bg-[#064E3B] text-white text-[13px] font-bold hover:bg-[#0F766E] transition-colors disabled:opacity-40"
          >
            {{ saving ? 'Creating…' : 'Create Year' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
