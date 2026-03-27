<script setup>
import StatsStrip      from '@/Components/Upload/StatsStrip.vue'
import DepartmentTable from '@/Components/Upload/DepartmentTable.vue'
import AddOfficeModal  from '@/Components/Upload/AddOfficeModal.vue'

defineProps({
  show:          { type: Boolean,   required: true },
  filename:      { type: String,    default: '' },
  parseStats:    { type: Object,    required: true },
  previewRows:   { type: Array,     required: true },
  groupedRows:   { type: Array,     required: true },
  selectedRows:  { type: Array,     required: true },
  totalBudget:   { type: Number,    required: true },
  parentOptions: { type: Array,     required: true },
  stage:         { type: String,    default: 'idle' },
  showAddOffice: { type: Boolean,   required: true },
  newOffice:     { type: Object,    required: true },
  showMovePopup: { type: Boolean,   required: true },
  moveTarget:    { type: Object,    default: null },
  dragRow:       { type: Object,    default: null },
  dragOver:      { type: String,    default: null },
  dragOverPos:   { type: String,    default: null },
  php:           { type: Function,  required: true },
  phpM:          { type: Function,  required: true },
})

const emit = defineEmits([
  'close',
  'confirm',
  'select-all',
  'toggle-row',
  'toggle-edit',
  'open-add-office',
  'save-new-office',
  'close-add-office',
  'update-new-office',
  'open-move-popup',
  'apply-move',
  'close-move-popup',
  'remove-from-parent',
  'drag-start',
  'drag-over',
  'drag-leave',
  'drop',
  'drag-end',
  'update-row-field',
])
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="font-family: 'Plus Jakarta Sans', sans-serif;">

        <!-- Backdrop -->
        <div class="absolute inset-0 bg-[#0D2137]/60 backdrop-blur-sm" @click="emit('close')" />

        <!-- Modal panel -->
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden">

          <!-- ── Header ──────────────────────────────────────── -->
          <div class="flex items-center justify-between px-7 py-5 border-b border-gray-100 shrink-0">
            <div>
              <h2 class="text-[16px] font-extrabold text-[#0D2137] tracking-tight">Preview Parsed Data</h2>
              <p class="text-[12px] text-gray-400 mt-0.5 font-medium">
                Review and edit before saving to database &nbsp;·&nbsp; {{ filename }}
              </p>
            </div>
            <button
              @click="emit('close')"
              class="w-8 h-8 rounded-xl text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>

          <!-- ── Stats strip ─────────────────────────────────── -->
          <StatsStrip
            :parse-stats="parseStats"
            :preview-rows="previewRows"
            :selected-rows="selectedRows"
            :total-budget="totalBudget"
            :php-m="phpM"
          />

          <!-- ── Instruction banner ──────────────────────────── -->
          <div class="flex items-center justify-between gap-3 bg-amber-50 border-b border-amber-100 px-7 py-2.5 shrink-0">
            <div class="flex items-center gap-2">
              <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
              <p class="text-[11px] text-amber-700 font-semibold">
                Click <strong>Edit</strong> on any row to correct the name or budget.
                Sub-offices are shown indented under their parent.
              </p>
            </div>
            <button
              @click="emit('open-add-office', '')"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#0D2137] text-white
                     text-[11px] font-bold hover:bg-[#1A5276] transition-colors shrink-0">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path d="M12 5v14M5 12h14"/>
              </svg>
              Add Office
            </button>
          </div>

          <!-- ── Department table ────────────────────────────── -->
          <DepartmentTable
            :grouped-rows="groupedRows"
            :preview-rows="previewRows"
            :selected-rows="selectedRows"
            :total-budget="totalBudget"
            :parent-options="parentOptions"
            :drag-row="dragRow"
            :drag-over="dragOver"
            :drag-over-pos="dragOverPos"
            :show-move-popup="showMovePopup"
            :move-target="moveTarget"
            :php="php"
            @toggle-row="emit('toggle-row', $event)"
            @toggle-edit="emit('toggle-edit', $event)"
            @select-all="emit('select-all', $event)"
            @open-move-popup="(row, idx) => emit('open-move-popup', row, idx)"
            @apply-move="emit('apply-move', $event)"
            @close-move-popup="emit('close-move-popup')"
            @remove-from-parent="emit('remove-from-parent', $event)"
            @open-add-office="emit('open-add-office', $event)"
            @drag-start="(e, r) => emit('drag-start', e, r)"
            @drag-over="(e, r) => emit('drag-over', e, r)"
            @drag-leave="emit('drag-leave')"
            @drop="(e, r) => emit('drop', e, r)"
            @drag-end="emit('drag-end')"
            @update-row-field="emit('update-row-field', $event)"
          />

          <!-- ── Add office mini-modal ───────────────────────── -->
          <AddOfficeModal
            :show="showAddOffice"
            :new-office="newOffice"
            :parent-options="parentOptions"
            @close="emit('close-add-office')"
            @save="emit('save-new-office')"
            @update:new-office="emit('update-new-office', $event)"
          />

          <!-- ── Footer / actions ───────────────────────────── -->
          <div class="flex items-center justify-between gap-4 px-7 py-4 border-t border-gray-100 bg-gray-50/50 shrink-0">
            <div class="flex items-center gap-2">
              <button @click="emit('select-all', true)"
                class="text-[11px] font-bold text-[#0D2137] hover:text-[#C9A84C] transition-colors">
                Select All
              </button>
              <span class="text-gray-300 text-xs">|</span>
              <button @click="emit('select-all', false)"
                class="text-[11px] font-bold text-gray-400 hover:text-red-500 transition-colors">
                Deselect All
              </button>
            </div>

            <div class="flex items-center gap-3">
              <button
                @click="emit('close')"
                class="px-5 py-2.5 rounded-xl border border-gray-200 text-[13px] font-semibold
                       text-gray-500 hover:border-gray-300 hover:text-gray-700 transition-all">
                Cancel
              </button>
              <button
                @click="emit('confirm')"
                :disabled="stage === 'saving' || !selectedRows.length"
                class="px-6 py-2.5 rounded-xl bg-[#0D2137] text-white font-bold text-[13px] tracking-wide
                       hover:bg-[#1A5276] transition-colors disabled:opacity-50
                       flex items-center gap-2 shadow-sm">
                <svg v-if="stage === 'saving'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
                {{ stage === 'saving'
                  ? 'Saving…'
                  : `Confirm & Save ${selectedRows.length} Dept${selectedRows.length !== 1 ? 's' : ''} to Database` }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .relative, .modal-leave-active .relative { transition: transform 0.25s ease; }
.modal-enter-from .relative { transform: scale(0.96) translateY(8px); }
.modal-leave-to   .relative { transform: scale(0.96) translateY(8px); }
</style>
