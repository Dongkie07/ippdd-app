<script setup>
defineProps({
  show:          { type: Boolean, required: true },
  moveTarget:    { type: Object,  default: null },
  parentOptions: { type: Array,   required: true },
})

const emit = defineEmits(['apply', 'close'])
</script>

<template>
  <tr v-if="show">
    <td colspan="6" class="px-0 py-0">
      <div class="mx-4 my-2 bg-white border-2 border-blue-200 rounded-2xl shadow-lg p-4">

        <!-- Header -->
        <div class="flex items-center justify-between mb-3">
          <div>
            <p class="text-[12px] font-extrabold text-[#0D2137]">
              Move "{{ moveTarget?.department }}"
            </p>
            <p class="text-[10px] text-gray-400 mt-0.5">
              Choose a parent or make it a top-level department
            </p>
          </div>
          <button @click="emit('close')" class="text-gray-300 hover:text-gray-500 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>

        <!-- Options -->
        <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto">

          <!-- Top level -->
          <button
            @click="emit('apply', null)"
            :class="['px-3 py-2 rounded-xl border-2 text-left transition-all text-[11px] font-bold',
              !moveTarget?.parent_dept
                ? 'border-[#0D2137] bg-[#0D2137]/5 text-[#0D2137]'
                : 'border-gray-100 hover:border-blue-200 text-gray-500 hover:text-[#0D2137]']">
            <div class="flex items-center gap-2">
              <span class="text-base">🏛</span>
              <div>
                <p>Top-level department</p>
                <p class="text-[9px] font-normal text-gray-400 mt-0.5">No parent — standalone</p>
              </div>
            </div>
          </button>

          <!-- Parent options -->
          <button
            v-for="p in parentOptions.filter(p => p.department !== moveTarget?.department)"
            :key="p.department"
            @click="emit('apply', p.department)"
            :class="['px-3 py-2 rounded-xl border-2 text-left transition-all',
              moveTarget?.parent_dept === p.department
                ? 'border-blue-400 bg-blue-50 text-[#0D2137]'
                : 'border-gray-100 hover:border-blue-200 text-gray-600 hover:text-[#0D2137]']">
            <div class="flex items-center gap-2">
              <span class="text-[10px] font-mono font-bold bg-[#0D2137]/8 text-[#0D2137]
                           px-1.5 py-0.5 rounded border border-[#0D2137]/10 shrink-0">
                {{ p.sheet_code || p.no || '?' }}
              </span>
              <span class="text-[11px] font-semibold truncate">{{ p.department }}</span>
              <span v-if="moveTarget?.parent_dept === p.department"
                class="text-[9px] text-blue-500 shrink-0">
                current
              </span>
            </div>
          </button>

        </div>
      </div>
    </td>
  </tr>
</template>
