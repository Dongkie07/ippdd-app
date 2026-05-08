<script setup>
import { useFormatters } from '@/composables/useFormatters'

defineProps({
  show: { type: Boolean, default: false },
  form: { type: Object, required: true },
  formMode: { type: String, default: 'add' },
  parentOptions: { type: Array, default: () => [] },
  funds: { type: Array, default: () => [] },
  formTotal: { type: Number, default: 0 },
  saving: { type: Boolean, default: false },
})

defineEmits(['close', 'submit'])

const { php } = useFormatters()
</script>

<template>
  <Transition name="panel">
    <div v-if="show" class="fixed inset-0 z-40 flex justify-end">
      <div class="absolute inset-0 bg-black/20 backdrop-blur-sm" @click="$emit('close')" />

      <div class="relative z-10 w-full max-w-md bg-white shadow-2xl flex flex-col h-full">
        <div class="flex items-center justify-between px-7 py-5 border-b border-gray-100 shrink-0">
          <div>
            <h2 class="text-[16px] font-extrabold text-[#064E3B]">
              {{ formMode === 'add' ? '+ Add Department' : 'Edit Department' }}
            </h2>
            <p class="text-[11px] text-gray-400 mt-0.5">Fiscal Year {{ form.year }}</p>
          </div>

          <button
            @click="$emit('close')"
            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors"
          >
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto px-7 py-6 space-y-5">
          <div>
            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Department / Office Name *</label>
            <input
              v-model="form.department"
              type="text"
              placeholder="e.g. Learning Resource Division"
              class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064E3B]/20 text-gray-700 bg-white"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Short Code</label>
              <input
                v-model="form.sheet_code"
                type="text"
                placeholder="e.g. 1a"
                class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064E3B]/20 text-gray-700 bg-white font-mono"
              />
            </div>

            <div>
              <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Sub-office Under</label>
              <select
                v-model="form.parent_dept"
                class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064E3B]/20 text-gray-700 bg-white"
              >
                <option value="">— Top-level —</option>
                <option v-for="parent in parentOptions" :key="parent.id" :value="parent.department">
                  {{ parent.department.length > 30 ? `${parent.department.slice(0, 29)}…` : parent.department }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Budget Allocation (₱)</label>
            <div class="grid grid-cols-2 gap-3">
              <div v-for="fund in funds" :key="fund.key">
                <label class="block text-[10px] font-bold mb-1" :style="{ color: fund.color }">
                  {{ fund.label }}
                </label>
                <input
                  v-model.number="form[fund.key]"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full px-3 py-2 text-[12px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064E3B]/20 font-mono text-gray-700 bg-gray-50/50"
                />
              </div>
            </div>

            <div class="mt-3 flex items-center justify-between bg-[#064E3B]/5 rounded-xl px-4 py-3 border border-[#064E3B]/10">
              <span class="text-[11px] font-bold text-gray-500">Computed Total</span>
              <span class="text-[15px] font-extrabold text-[#064E3B]">{{ php(formTotal) }}</span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Status</label>
              <select
                v-model="form.status"
                class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064E3B]/20 text-gray-700 bg-white"
              >
                <option>Approved</option>
                <option>Pending</option>
                <option>For Revision</option>
              </select>
            </div>

            <div class="flex flex-col justify-end pb-1">
              <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Has Sub-offices</label>
              <button @click="form.is_parent = !form.is_parent" class="flex items-center gap-2.5 mt-1">
                <div :class="['w-10 h-5 rounded-full relative transition-colors shrink-0', form.is_parent ? 'bg-[#064E3B]' : 'bg-gray-200']">
                  <span :class="['absolute top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform', form.is_parent ? 'translate-x-5' : 'translate-x-0.5']" />
                </div>
                <span class="text-[12px] text-gray-500">{{ form.is_parent ? 'Yes' : 'No' }}</span>
              </button>
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">
              Remarks <span class="text-gray-300 font-normal">(optional)</span>
            </label>
            <textarea
              v-model="form.remarks"
              rows="3"
              placeholder="Notes, observations, or comments…"
              class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#064E3B]/20 text-gray-700 bg-white resize-none"
            />
          </div>
        </div>

        <div class="px-7 py-5 border-t border-gray-100 flex gap-3 shrink-0">
          <button
            @click="$emit('close')"
            class="flex-1 py-2.5 rounded-xl border border-gray-200 text-[13px] font-semibold text-gray-500 hover:border-gray-300 transition-all"
          >
            Cancel
          </button>

          <button
            @click="$emit('submit')"
            :disabled="saving || !form.department.trim()"
            class="flex-1 py-2.5 rounded-xl bg-[#064E3B] text-white text-[13px] font-bold hover:bg-[#0F766E] transition-colors disabled:opacity-40"
          >
            {{ saving ? 'Saving…' : (formMode === 'add' ? 'Add Department' : 'Save Changes') }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
