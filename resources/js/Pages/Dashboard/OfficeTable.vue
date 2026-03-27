<script setup>
/**
 * Dashboard/OfficeTable.vue
 * ─────────────────────────────────────────
 * Expandable office breakdown table.
 * Shows all top-level departments with expand/collapse
 * to reveal sub-offices and their budgets.
 *
 * Props:
 *   rows  Array — sorted, deduplicated dept rows (from Dashboard.vue)
 *
 * Each row shape:
 *   { department, budget_total, own_budget,
 *     budget_fund_101/164/161/163,
 *     children: [{ department, budget_total, budget_fund_* }] }
 */
import SectionCard from '@/Components/SectionCard.vue'
import { ref } from 'vue'
import { useFormatters } from '@/composables/useFormatters'
import { useTableSort }  from '@/composables/useTableSort'
import { useExpandRows } from '@/composables/useExpandRows'

const props = defineProps({
  rows:  { type: Array,  default: () => [] },
  year:  { type: Number, required: true },
})
const emit = defineEmits(['update:sort'])

const { php, phpM } = useFormatters()
const { setSort, sortIcon } = useTableSort('budget_total', 'desc')
const { toggleExpand, isExpanded } = useExpandRows()

const search = ref('')
const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return q ? props.rows.filter(d => d.department.toLowerCase().includes(q)) : props.rows
})

// Budget field accessors — supports old and new DB field names
const bget = d => d.budget_total    ?? d.budget   ?? 0
const f101 = d => d.budget_fund_101 ?? d.fund_101 ?? 0
const f164 = d => d.budget_fund_164 ?? d.fund_164 ?? 0
const f161 = d => d.budget_fund_161 ?? d.fund_161 ?? 0
const f163 = d => d.budget_fund_163 ?? d.fund_163 ?? 0

// Column definitions — drives both <thead> and <tbody> cells
// Makes adding/removing columns a one-line change
const COLUMNS = [
  { key: 'budget_total',    label: 'Total Budget', sortable: true,  align: 'right', get: d => bget(d) },
  { key: 'budget_fund_101', label: 'Fund 101',     sortable: true,  align: 'right', get: d => f101(d) },
  { key: 'budget_fund_164', label: 'Fund 164',     sortable: true,  align: 'right', get: d => f164(d) },
  { key: 'budget_fund_161', label: 'Fund 161',     sortable: true,  align: 'right', get: d => f161(d) },
  { key: 'budget_fund_163', label: 'Fund 163',     sortable: true,  align: 'right', get: d => f163(d) },
]
</script>

<template>
  <SectionCard
    :title="`Office Breakdown — FY ${year}`"
    :subtitle="`${rows.length} offices · click headers to sort · click row to expand sub-offices`"
    :noPad="true">

    <template #actions>
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400"
          fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
        </svg>
        <input v-model="search" type="text" placeholder="Search office…"
          class="pl-8 pr-4 py-2 text-[12px] border border-gray-200 rounded-xl w-48
                 focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20
                 text-gray-600 placeholder-gray-300 bg-gray-50/50" />
      </div>
    </template>

    <div class="overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead>
          <tr class="border-b-2 border-gray-100">
            <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-10">#</th>
            <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
              <button @click="setSort('department'); emit('update:sort')" class="hover:text-[#0D2137]">
                Department{{ sortIcon('department') }}
              </button>
            </th>
            <th v-for="col in COLUMNS" :key="col.key"
              class="text-right px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
              <button v-if="col.sortable"
                @click="setSort(col.key); emit('update:sort')"
                class="hover:text-[#0D2137] ml-auto flex items-center gap-1">
                {{ col.label }}{{ sortIcon(col.key) }}
              </button>
              <span v-else>{{ col.label }}</span>
            </th>
          </tr>
        </thead>

        <tbody>
          <template v-for="(d, i) in filtered" :key="d.department + '_' + year">

            <!-- Parent row -->
            <tr @click="d.children?.length ? toggleExpand(d.department) : null"
              :class="[
                'border-b border-gray-50 transition-colors group',
                d.children?.length ? 'cursor-pointer hover:bg-[#0D2137]/[0.03]' : 'hover:bg-[#0D2137]/[0.02]',
                isExpanded(d.department) ? 'bg-[#0D2137]/[0.025]' : ''
              ]">
              <td class="px-6 py-3.5 text-gray-300 font-mono text-[11px] font-bold">
                {{ String(i + 1).padStart(2, '0') }}
              </td>
              <td class="px-6 py-3.5">
                <div class="flex items-center gap-2">
                  <span v-if="d.children?.length"
                    :class="['transition-transform duration-200 text-gray-300 group-hover:text-[#0D2137]',
                      isExpanded(d.department) ? 'rotate-90' : '']">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path d="M9 18l6-6-6-6"/>
                    </svg>
                  </span>
                  <span v-else class="w-3.5 shrink-0" />
                  <span class="font-semibold text-[#0D2137] group-hover:text-[#1A5276] transition-colors">
                    {{ d.department }}
                  </span>
                  <span v-if="d.children?.length"
                    class="text-[9px] font-bold text-blue-400 bg-blue-50 border border-blue-100 px-1.5 py-0.5 rounded-full">
                    {{ d.children.length }} sub
                  </span>
                  <span v-if="d.children?.length && d.own_budget > 0"
                    class="text-[9px] text-gray-300 font-mono hidden group-hover:inline">
                    own {{ phpM(d.own_budget) }}
                  </span>
                </div>
              </td>
              <!-- Budget columns -->
              <td class="px-6 py-3.5 text-right">
                <span class="font-mono font-bold text-[#0D2137] text-[13px]">{{ php(bget(d)) }}</span>
                <p v-if="d.children?.length" class="text-[9px] text-gray-300 font-mono mt-0.5">incl. sub-offices</p>
              </td>
              <td v-for="col in COLUMNS.slice(1)" :key="col.key"
                class="px-6 py-3.5 text-right font-mono text-[12px] text-gray-500">
                {{ col.get(d) > 0 ? php(col.get(d)) : '—' }}
              </td>
            </tr>

            <!-- Child rows -->
            <template v-if="d.children?.length && isExpanded(d.department)">
              <tr v-for="child in d.children" :key="child.department"
                class="border-b border-gray-50/80 bg-slate-50/60 hover:bg-blue-50/20 transition-colors">
                <td class="px-6 py-2.5" />
                <td class="py-2.5 pl-14 pr-6">
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-px bg-gray-200 shrink-0" />
                    <div class="w-1 h-1 rounded-full bg-gray-300 shrink-0" />
                    <span class="text-[12px] font-medium text-gray-600">{{ child.department }}</span>
                    <span v-if="child.budget_total === 0"
                      class="text-[9px] font-bold text-amber-400 bg-amber-50 border border-amber-100 px-1.5 py-0.5 rounded-full">
                      No allocation
                    </span>
                  </div>
                </td>
                <td class="px-6 py-2.5 text-right font-mono text-[12px] font-semibold text-gray-600">
                  {{ child.budget_total > 0 ? php(child.budget_total) : '—' }}
                </td>
                <td v-for="col in COLUMNS.slice(1)" :key="col.key"
                  class="px-6 py-2.5 text-right font-mono text-[11px] text-gray-400">
                  {{ col.get(child) > 0 ? php(col.get(child)) : '—' }}
                </td>
              </tr>

              <!-- Subtotal -->
              <tr class="border-b-2 border-[#0D2137]/10 bg-[#0D2137]/[0.015]">
                <td class="px-6 py-2" />
                <td class="py-2 pl-14 pr-6">
                  <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">
                    {{ d.department }} — subtotal
                  </span>
                </td>
                <td class="px-6 py-2 text-right font-mono font-extrabold text-[#0D2137] text-[12px]">
                  {{ php(bget(d)) }}
                </td>
                <td colspan="4" class="px-6 py-2 text-right text-[10px] text-gray-400 font-mono">
                  {{ d.children.length }} sub-offices · own {{ phpM(d.own_budget ?? 0) }}
                </td>
              </tr>
            </template>

          </template>

          <tr v-if="filtered.length === 0">
            <td colspan="7" class="px-6 py-14 text-center text-gray-300 text-sm">No results found.</td>
          </tr>
        </tbody>

        <!-- Footer totals -->
        <tfoot v-if="filtered.length > 0">
          <tr class="border-t-2 border-gray-200 bg-[#0D2137]/[0.02]">
            <td colspan="2" class="px-6 py-3.5">
              <span class="text-[11px] font-extrabold uppercase tracking-widest text-gray-500">Total</span>
            </td>
            <td v-for="col in COLUMNS" :key="col.key" class="px-6 py-3.5 text-right font-mono font-extrabold text-[#0D2137]">
              {{ php(filtered.reduce((s, d) => s + col.get(d), 0)) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </SectionCard>
</template>
