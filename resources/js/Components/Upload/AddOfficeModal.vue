<script setup>
import { getCurrentInstance } from 'vue' // ← you don't actually need this, remove it

const props = defineProps({           // ← single call, saved to `props`
  show:          { type: Boolean,  required: true },
  newOffice:     { type: Object,   required: true },
  parentOptions: { type: Array,    required: true },
})

const emit = defineEmits([
  'close',
  'save',
  'update:newOffice',
])

const update = (field, value) => {
  emit('update:newOffice', { ...props.newOffice, [field]: value })  // ✅ props is defined above
}
</script>

<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="absolute inset-0 z-20 flex items-center justify-center bg-[#0D2137]/40 backdrop-blur-sm rounded-3xl">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-6 p-6">

        <!-- Header -->
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-[15px] font-extrabold text-[#0D2137]">Add New Office</h3>
          <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <!-- Office name -->
          <div>
            <label class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400 block mb-1">
              Office / Department Name *
            </label>
            <input
              :value="newOffice.department"
              @input="update('department', $event.target.value)"
              type="text"
              placeholder="e.g. Library Services Unit"
              class="w-full px-3 py-2.5 text-[13px] border border-gray-200 rounded-xl
                     focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 text-[#0D2137] font-semibold"
            />
          </div>

          <!-- Short code -->
          <div>
            <label class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400 block mb-1">
              Short Code
            </label>
            <input
              :value="newOffice.sheet_code"
              @input="update('sheet_code', $event.target.value)"
              type="text"
              placeholder="e.g. LSU (auto-generated if blank)"
              class="w-full px-3 py-2.5 text-[13px] border border-gray-200 rounded-xl
                     focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 font-mono text-gray-600"
            />
          </div>

          <!-- Budget -->
          <div>
            <label class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400 block mb-1">
              Total Budget (₱)
            </label>
            <input
              :value="newOffice.budget_total"
              @input="update('budget_total', parseFloat($event.target.value) || 0)"
              type="number"
              placeholder="0.00"
              class="w-full px-3 py-2.5 text-[13px] border border-gray-200 rounded-xl
                     focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 font-mono text-[#0D2137]"
            />
          </div>

          <!-- Parent department -->
          <div>
            <label class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400 block mb-1">
              Sub-Office Under (optional)
            </label>
            <select
              :value="newOffice.parent_dept"
              @change="update('parent_dept', $event.target.value)"
              class="w-full px-3 py-2.5 text-[13px] border border-gray-200 rounded-xl
                     focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 text-gray-600 bg-white">
              <option value="">— None (top-level department) —</option>
              <option v-for="p in parentOptions" :key="p.department" :value="p.department">
                {{ p.department }}
              </option>
            </select>
            <p class="text-[10px] text-gray-400 mt-1">Leave blank if this is a top-level department</p>
          </div>

          <!-- Is parent toggle -->
          <div v-if="!newOffice.parent_dept" class="flex items-center gap-3">
            <button
              @click="update('is_parent', !newOffice.is_parent)"
              :class="['w-9 h-5 rounded-full transition-colors relative',
                newOffice.is_parent ? 'bg-[#0D2137]' : 'bg-gray-200']">
              <span :class="['absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform',
                newOffice.is_parent ? 'translate-x-4' : 'translate-x-0.5']" />
            </button>
            <span class="text-[12px] text-gray-500">This office has sub-offices</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 mt-6">
          <button
            @click="emit('close')"
            class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-[13px] font-semibold
                   text-gray-500 hover:border-gray-300 transition-all">
            Cancel
          </button>
          <button
            @click="emit('save')"
            :disabled="!newOffice.department.trim()"
            class="flex-1 px-4 py-2.5 rounded-xl bg-[#0D2137] text-white text-[13px] font-bold
                   hover:bg-[#1A5276] transition-colors disabled:opacity-40">
            Add to List
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
