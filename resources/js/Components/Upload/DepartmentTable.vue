<script setup>
import DepartmentRow   from '@/Components/Upload/DepartmentRow.vue'
import MoveParentPopup from '@/Components/Upload/MoveParentPopup.vue'

// ── Props ─────────────────────────────────────────────────────
// defineProps is a compiler macro — no import needed, call once only
const props = defineProps({
  groupedRows:   { type: Array,    required: true },
  previewRows:   { type: Array,    required: true },
  selectedRows:  { type: Array,    required: true },
  totalBudget:   { type: Number,   required: true },
  parentOptions: { type: Array,    required: true },
  dragRow:       { type: Object,   default: null  },
  dragOver:      { type: String,   default: null  },
  dragOverPos:   { type: String,   default: null  },
  showMovePopup: { type: Boolean,  required: true },
  moveTarget:    { type: Object,   default: null  },
  php:           { type: Function, required: true },
})

// ── Emits ─────────────────────────────────────────────────────
const emit = defineEmits([
  'toggle-row', 'toggle-edit', 'select-all',
  'open-move-popup', 'apply-move', 'close-move-popup',
  'remove-from-parent', 'open-add-office',
  'drag-start', 'drag-over', 'drag-leave', 'drop', 'drag-end',
  'update-row-field',
])

// ── Helpers ───────────────────────────────────────────────────
// Find the original index of a row inside previewRows
// (groupedRows is a display-order copy — this maps back to the source array)
const previewIndex = (row) =>
  props.previewRows.findIndex(
    r => r.department === row.department && r.sheet_code === row.sheet_code
  )
</script>

<template>
  <div class="overflow-y-auto flex-1">
    <table class="w-full text-[13px]">

      <!-- Sticky header -->
      <thead class="sticky top-0 bg-white z-10">
        <tr class="border-b-2 border-gray-100">
          <!-- Select-all checkbox -->
          <th class="px-5 py-3 w-10">
            <button
              @click="emit('select-all', selectedRows.length < previewRows.length)"
              :class="['w-4 h-4 rounded border-2 flex items-center justify-center transition-all mx-auto',
                selectedRows.length === previewRows.length
                  ? 'bg-[#0D2137] border-[#0D2137]'
                  : selectedRows.length > 0
                    ? 'bg-[#0D2137]/30 border-[#0D2137]/40'
                    : 'border-gray-300']">
              <svg v-if="selectedRows.length > 0" class="w-2.5 h-2.5 text-white"
                fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </button>
          </th>
          <th class="text-left px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-8">#</th>
          <th class="text-left px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department / Office</th>
          <th class="text-left px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-20">Code</th>
          <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-40">Budget (₱)</th>
          <th class="px-4 py-3 w-20"></th>
        </tr>
      </thead>

      <tbody>
        <template v-for="(row, gi) in groupedRows" :key="row.department + row.sheet_code">

          <!-- Drop-above highlight bar -->
          <tr v-if="dragOver === row.department && dragOverPos === 'above'"
            class="h-0.5 bg-blue-400 opacity-60" />

          <!-- Department row -->
          <DepartmentRow
            :row="row"
            :row-index="previewIndex(row)"
            :grouped-index="gi"
            :drag-row="dragRow"
            :drag-over="dragOver"
            :drag-over-pos="dragOverPos"
            :grouped-rows="groupedRows"
            :php="php"
            @toggle-row="emit('toggle-row', $event)"
            @toggle-edit="emit('toggle-edit', $event)"
            @open-move-popup="emit('open-move-popup', $event)"
            @remove-from-parent="emit('remove-from-parent', $event)"
            @open-add-office="emit('open-add-office', $event)"
            @drag-start="(e, r) => emit('drag-start', e, r)"
            @drag-over="(e, r) => emit('drag-over', e, r)"
            @drag-leave="emit('drag-leave')"
            @drop="(e, r) => emit('drop', e, r)"
            @drag-end="emit('drag-end')"
            @update-field="({ field, value }) => emit('update-row-field', { row, field, value })"
          />

          <!-- Drop-below highlight bar -->
          <tr v-if="dragOver === row.department && dragOverPos === 'below'"
            class="h-0.5 bg-blue-400 opacity-60" />

        </template>

        <!-- Move-to-parent popup (renders as a table row inline) -->
        <MoveParentPopup
          :show="showMovePopup"
          :move-target="moveTarget"
          :parent-options="parentOptions"
          @apply="emit('apply-move', $event)"
          @close="emit('close-move-popup')"
        />
      </tbody>

      <!-- Totals footer -->
      <tfoot class="sticky bottom-0 bg-white border-t-2 border-gray-200">
        <tr class="bg-[#0D2137]/[0.025]">
          <td colspan="4" class="px-5 py-3.5">
            <span class="text-[11px] font-extrabold uppercase tracking-widest text-gray-500">
              Total — {{ selectedRows.length }} of {{ previewRows.length }} selected
            </span>
          </td>
          <td class="px-4 py-3.5 text-right font-mono font-extrabold text-[#0D2137]">
            {{ php(totalBudget) }}
          </td>
          <td />
        </tr>
      </tfoot>

    </table>
  </div>
</template>