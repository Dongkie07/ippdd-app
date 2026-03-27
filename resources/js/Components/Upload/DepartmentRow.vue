<script setup>
defineProps({
  row:          { type: Object,   required: true },
  rowIndex:     { type: Number,   required: true },  // index in previewRows
  groupedIndex: { type: Number,   required: true },  // index in groupedRows for display
  dragRow:      { type: Object,   default: null },
  dragOver:     { type: String,   default: null },
  dragOverPos:  { type: String,   default: null },
  groupedRows:  { type: Array,    required: true },
  php:          { type: Function, required: true },
})

const emit = defineEmits([
  'toggle-row',
  'toggle-edit',
  'open-move-popup',
  'remove-from-parent',
  'open-add-office',
  'drag-start',
  'drag-over',
  'drag-leave',
  'drop',
  'drag-end',
  'update-field',   // { field, value }
])
</script>

<template>
  <tr
    draggable="true"
    @dragstart="emit('drag-start', $event, row)"
    @dragover="emit('drag-over', $event, row)"
    @dragleave="emit('drag-leave')"
    @drop="emit('drop', $event, row)"
    @dragend="emit('drag-end')"
    :class="[
      'border-b transition-all cursor-grab active:cursor-grabbing',
      row._isChild ? 'border-gray-50 bg-slate-50/50' : 'border-gray-100',
      row.selected
        ? (row._isChild ? 'hover:bg-blue-50/20' : 'hover:bg-blue-50/30')
        : 'opacity-40 bg-gray-50/80',
      dragRow?.department === row.department ? 'opacity-30 scale-[0.99]' : '',
      dragOver === row.department && dragOverPos === 'into'
        ? 'ring-2 ring-inset ring-blue-400 bg-blue-50/40' : '',
    ]">

    <!-- Drag handle + checkbox -->
    <td class="px-3 py-3 text-center w-12">
      <div class="flex items-center gap-1.5 justify-center">
        <svg class="w-3 h-3 text-gray-200 hover:text-gray-400 shrink-0 cursor-grab" fill="currentColor" viewBox="0 0 24 24">
          <circle cx="9"  cy="5"  r="1.5"/><circle cx="15" cy="5"  r="1.5"/>
          <circle cx="9"  cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
          <circle cx="9"  cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/>
        </svg>
        <button
          @click.stop="emit('toggle-row', rowIndex)"
          :class="['w-4 h-4 rounded border-2 flex items-center justify-center transition-all',
            row.selected ? 'bg-[#0D2137] border-[#0D2137]' : 'border-gray-300 bg-white']">
          <svg v-if="row.selected" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </button>
      </div>
    </td>

    <!-- Row number -->
    <td class="px-3 py-3 text-gray-300 font-mono text-[10px] font-bold w-8">
      <span v-if="!row._isChild">{{ String(rowIndex + 1).padStart(2, '0') }}</span>
      <span v-else class="text-[9px] text-blue-300">{{ row.no || '·' }}</span>
    </td>

    <!-- Department name -->
    <td class="py-3" :class="row._isChild ? 'pl-8 pr-4' : 'px-3'">
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Child indent indicator -->
        <div v-if="row._isChild" class="flex items-center gap-1 shrink-0">
          <div class="w-3 h-px bg-gray-300"/>
          <div class="w-1 h-1 rounded-full bg-gray-300"/>
        </div>
        <!-- Parent badge -->
        <span
          v-if="!row._isChild && groupedRows.some(r => r._isChild && r.parent_dept === row.department)"
          class="text-[8px] font-bold text-blue-500 bg-blue-50 border border-blue-100
                 px-1.5 py-0.5 rounded-full shrink-0 uppercase tracking-wide">
          parent
        </span>
        <!-- Editable name -->
        <input
          v-if="row._editing"
          :value="row.department"
          @input="emit('update-field', { field: 'department', value: $event.target.value })"
          @keyup.enter="emit('toggle-edit', rowIndex)"
          class="flex-1 text-[13px] font-semibold text-[#0D2137] border-0 border-b-2
                 border-[#C9A84C] outline-none bg-transparent pb-0.5 focus:ring-0"
        />
        <span v-else :class="['font-semibold', row._isChild ? 'text-gray-600 text-[12px]' : 'text-[#0D2137] text-[13px]']">
          {{ row.department }}
        </span>
        <!-- Under badge -->
        <span v-if="row._isChild && row.parent_dept"
          class="text-[9px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded font-mono">
          under {{ row.parent_dept }}
        </span>
        <!-- Drop hint -->
        <span
          v-if="dragOver === row.department && dragOverPos === 'into' && !row._isChild"
          class="text-[9px] font-bold text-blue-500 bg-blue-100 px-1.5 py-0.5 rounded-full animate-pulse">
          drop here to nest →
        </span>
      </div>
    </td>

    <!-- Sheet code -->
    <td class="px-3 py-3">
      <span :class="['text-[10px] font-mono font-bold px-2 py-0.5 rounded-lg border whitespace-nowrap',
        row._isChild
          ? 'bg-gray-50 text-gray-400 border-gray-100'
          : 'bg-[#0D2137]/6 text-[#0D2137] border-[#0D2137]/10']">
        {{ row.sheet_code || row.no || '—' }}
      </span>
    </td>

    <!-- Budget -->
    <td class="px-3 py-3 text-right">
      <input
        v-if="row._editing"
        :value="row.budget_total"
        @input="emit('update-field', { field: 'budget_total', value: parseFloat($event.target.value) || 0 })"
        @keyup.enter="emit('toggle-edit', rowIndex)"
        type="number"
        class="w-32 text-right text-[13px] font-mono font-bold text-[#0D2137]
               border-0 border-b-2 border-[#C9A84C] outline-none bg-transparent pb-0.5"
      />
      <template v-else>
        <span
          v-if="(row.budget_total ?? row.budget ?? 0) == 0"
          class="text-[10px] font-bold text-amber-400 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-full">
          No allocation
        </span>
        <span v-else :class="['font-mono font-bold text-[13px]', row._isChild ? 'text-gray-500' : 'text-[#0D2137]']">
          {{ php(row.budget_total ?? row.budget ?? 0) }}
        </span>
      </template>
    </td>

    <!-- Actions -->
    <td class="px-3 py-3">
      <div class="flex items-center gap-1 justify-end">
        <!-- Move -->
        <button
          v-if="!row._editing"
          @click.stop="emit('open-move-popup', row, rowIndex)"
          class="text-[10px] font-bold px-2 py-1 rounded-lg text-gray-300
                 hover:text-blue-500 hover:bg-blue-50 transition-all"
          title="Move to parent / detach">
          ⇅
        </button>
        <!-- Remove from parent -->
        <button
          v-if="row._isChild && !row._editing"
          @click.stop="emit('remove-from-parent', row)"
          class="text-[10px] font-bold px-1.5 py-1 rounded-lg text-gray-200
                 hover:text-red-400 hover:bg-red-50 transition-all"
          title="Remove from parent (make top-level)">
          ✕
        </button>
        <!-- Add sub-office -->
        <button
          v-if="!row._isChild && !row._editing"
          @click.stop="emit('open-add-office', row.department)"
          class="text-[10px] font-bold px-2 py-1 rounded-lg text-emerald-500
                 hover:text-emerald-700 hover:bg-emerald-50 border border-emerald-100
                 hover:border-emerald-200 transition-all whitespace-nowrap"
          title="Add sub-office under this department">
          + Sub
        </button>
        <!-- Edit / Done -->
        <button
          @click.stop="emit('toggle-edit', rowIndex)"
          :class="['text-[11px] font-bold px-2.5 py-1 rounded-lg transition-all',
            row._editing
              ? 'bg-emerald-100 text-emerald-700'
              : 'text-gray-400 hover:text-[#0D2137] hover:bg-gray-100']">
          {{ row._editing ? '✓ Done' : 'Edit' }}
        </button>
      </div>
    </td>

  </tr>
</template>
