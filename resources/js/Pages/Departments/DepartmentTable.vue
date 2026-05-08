<script setup>
import { computed } from 'vue'
import { useFormatters } from '@/composables/useFormatters'

defineProps({
  rows: { type: Array, default: () => [] },
  funds: { type: Array, default: () => [] },
  totalBudget: { type: Number, default: 0 },
})

defineEmits(['edit', 'delete'])

const { php } = useFormatters()

const statusClass = (status) => {
  if (status === 'Approved') return 'text-emerald-700 bg-emerald-50 border-emerald-100'
  if (status === 'For Revision') return 'text-red-600 bg-red-50 border-red-100'
  return 'text-amber-700 bg-amber-50 border-amber-100'
}

const topLevelRows = computed(() => (rows) => rows.filter((row) => !row.parent_dept))
const fundTotal = (rows, key) => topLevelRows.value(rows).reduce((sum, row) => sum + (row[key] ?? 0), 0)
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-[12px]">
        <thead>
          <tr class="border-b-2 border-gray-100 bg-gray-50/60">
            <th class="text-left px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-10">#</th>
            <th class="text-left px-4 py-3.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department / Office</th>
            <th class="text-right px-4 py-3.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Total</th>
            <th
              v-for="fund in funds"
              :key="fund.key"
              class="text-right px-4 py-3.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400"
            >
              {{ fund.label }}
            </th>
            <th class="text-center px-4 py-3.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Status</th>
            <th class="px-4 py-3.5 w-28" />
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="row in rows"
            :key="row.id"
            :class="[
              'border-b border-gray-50 transition-colors',
              row.parent_dept ? 'hover:bg-slate-50 bg-slate-50/30' : 'hover:bg-[#064E3B]/[0.02]',
            ]"
          >
            <td class="px-5 py-3 text-gray-300 font-mono text-[10px] font-bold">
              {{ row.no || '—' }}
            </td>

            <td class="px-4 py-3">
              <div class="flex items-center gap-2 min-w-0">
                <template v-if="row.parent_dept">
                  <div class="flex items-center gap-1 shrink-0">
                    <div class="w-4 h-px bg-gray-200" />
                    <div class="w-1.5 h-1.5 rounded-full bg-gray-300" />
                  </div>
                </template>

                <span :class="[
                  'truncate font-semibold',
                  row.parent_dept ? 'text-gray-500 text-[12px]' : 'text-[#064E3B] text-[13px]',
                ]">
                  {{ row.department }}
                </span>

                <span
                  v-if="row.is_parent"
                  class="text-[8px] font-bold text-emerald-500 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded-full shrink-0"
                >
                  parent
                </span>

                <span
                  v-if="row.parent_dept"
                  class="text-[9px] text-gray-300 bg-gray-50 px-1.5 py-0.5 rounded font-mono shrink-0 hidden xl:inline"
                >
                  ↳ {{ row.parent_dept }}
                </span>
              </div>
            </td>

            <td class="px-4 py-3 text-right font-mono font-bold text-[#064E3B]">
              {{ (row.budget_total ?? 0) > 0 ? php(row.budget_total) : '—' }}
            </td>

            <td
              v-for="fund in funds"
              :key="fund.key"
              class="px-4 py-3 text-right font-mono text-gray-400 text-[11px]"
            >
              {{ (row[fund.key] ?? 0) > 0 ? php(row[fund.key]) : '—' }}
            </td>

            <td class="px-4 py-3 text-center">
              <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border whitespace-nowrap', statusClass(row.status)]">
                {{ row.status }}
              </span>
            </td>

            <td class="px-4 py-3">
              <div class="flex items-center gap-1 justify-end shrink-0">
                <button
                  @click="$emit('edit', row)"
                  class="text-[11px] font-bold px-2.5 py-1.5 rounded-lg text-gray-400 hover:text-[#064E3B] hover:bg-gray-100 transition-all whitespace-nowrap"
                >
                  Edit
                </button>

                <button
                  @click="$emit('delete', row)"
                  class="p-1.5 rounded-lg text-gray-200 hover:text-red-500 hover:bg-red-50 transition-all shrink-0"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6l-1 14H6L5 6M10 11v6M14 11v6" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>

        <tfoot v-if="rows.length > 0">
          <tr class="border-t-2 border-gray-100 bg-[#064E3B]/[0.025]">
            <td colspan="2" class="px-5 py-3">
              <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">
                Grand Total — {{ topLevelRows(rows).length }} top-level offices
              </span>
            </td>
            <td class="px-4 py-3 text-right font-mono font-extrabold text-[#064E3B] text-[13px]">
              {{ php(totalBudget) }}
            </td>
            <td
              v-for="fund in funds"
              :key="fund.key"
              class="px-4 py-3 text-right font-mono font-bold text-[#064E3B] text-[12px]"
            >
              {{ php(fundTotal(rows, fund.key)) }}
            </td>
            <td colspan="2" />
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>
