<script setup>
/**
 * Dashboard/OfficeTable.vue
 * Expandable office breakdown table.
 */
import SectionCard from '@/Components/SectionCard.vue'
import { ref, computed } from 'vue'
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
        <svg class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#8FA79B]"
          fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
        </svg>
        <input v-model="search" type="text" placeholder="Search office…"
          class="w-52 rounded-2xl border border-[#DDEDE3] bg-[#F8FCF9] py-2 pl-8 pr-4 text-[12px] font-semibold
                 text-[#064E3B] placeholder-[#8FA79B] focus:outline-none focus:ring-2 focus:ring-[#168A4A]/20" />
      </div>
    </template>

    <div class="overflow-x-auto">
      <table class="w-full text-[13px]">
        <thead class="bg-[#F8FCF9]">
          <tr class="border-b-2 border-[#E6F2EA]">
            <th class="w-10 px-6 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-[#8FA79B]">#</th>
            <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-[#8FA79B]">
              <button @click="setSort('department'); emit('update:sort')" class="hover:text-[#064E3B]">
                Department{{ sortIcon('department') }}
              </button>
            </th>
            <th v-for="col in COLUMNS" :key="col.key"
              class="px-6 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-[#8FA79B]">
              <button v-if="col.sortable"
                @click="setSort(col.key); emit('update:sort')"
                class="ml-auto flex items-center gap-1 hover:text-[#064E3B]">
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
                'group border-b border-[#E6F2EA]/70 transition-colors',
                d.children?.length ? 'cursor-pointer hover:bg-[#ECFDF3]/70' : 'hover:bg-[#ECFDF3]/50',
                isExpanded(d.department) ? 'bg-[#ECFDF3]' : ''
              ]">
              <td class="px-6 py-3.5 font-mono text-[11px] font-black text-[#8FA79B]">
                {{ String(i + 1).padStart(2, '0') }}
              </td>
              <td class="px-6 py-3.5">
                <div class="flex items-center gap-2">
                  <span v-if="d.children?.length"
                    :class="['text-[#8FA79B] transition-transform duration-200 group-hover:text-[#064E3B]',
                      isExpanded(d.department) ? 'rotate-90' : '']">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path d="M9 18l6-6-6-6"/>
                    </svg>
                  </span>
                  <span v-else class="w-3.5 shrink-0" />
                  <span class="font-bold text-[#064E3B] transition-colors group-hover:text-[#168A4A]">
                    {{ d.department }}
                  </span>
                  <span v-if="d.children?.length"
                    class="rounded-full border border-[#B7F4CE] bg-[#ECFDF3] px-1.5 py-0.5 text-[9px] font-black text-[#168A4A]">
                    {{ d.children.length }} sub
                  </span>
                  <span v-if="d.children?.length && d.own_budget > 0"
                    class="hidden font-mono text-[9px] text-[#8FA79B] group-hover:inline">
                    own {{ phpM(d.own_budget) }}
                  </span>
                </div>
              </td>
              <!-- Budget columns -->
              <td class="px-6 py-3.5 text-right">
                <span class="font-mono text-[13px] font-black text-[#064E3B]">{{ php(bget(d)) }}</span>
                <p v-if="d.children?.length" class="mt-0.5 font-mono text-[9px] text-[#8FA79B]">incl. sub-offices</p>
              </td>
              <td v-for="col in COLUMNS.slice(1)" :key="col.key"
                class="px-6 py-3.5 text-right font-mono text-[12px] font-semibold text-[#64746B]">
                {{ col.get(d) > 0 ? php(col.get(d)) : '—' }}
              </td>
            </tr>

            <!-- Child rows -->
            <template v-if="d.children?.length && isExpanded(d.department)">
              <tr v-for="child in d.children" :key="child.department"
                class="border-b border-[#E6F2EA]/70 bg-[#F8FCF9] transition-colors hover:bg-[#ECFDF3]">
                <td class="px-6 py-2.5" />
                <td class="py-2.5 pl-14 pr-6">
                  <div class="flex items-center gap-2">
                    <div class="h-px w-3 shrink-0 bg-[#B7F4CE]" />
                    <div class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#53D28C]" />
                    <span class="text-[12px] font-semibold text-[#64746B]">{{ child.department }}</span>
                    <span v-if="child.budget_total === 0"
                      class="rounded-full border border-amber-100 bg-amber-50 px-1.5 py-0.5 text-[9px] font-black text-amber-500">
                      No allocation
                    </span>
                  </div>
                </td>
                <td class="px-6 py-2.5 text-right font-mono text-[12px] font-bold text-[#64746B]">
                  {{ child.budget_total > 0 ? php(child.budget_total) : '—' }}
                </td>
                <td v-for="col in COLUMNS.slice(1)" :key="col.key"
                  class="px-6 py-2.5 text-right font-mono text-[11px] text-[#8FA79B]">
                  {{ col.get(child) > 0 ? php(col.get(child)) : '—' }}
                </td>
              </tr>

              <!-- Subtotal -->
              <tr class="border-b-2 border-[#B7F4CE] bg-[#ECFDF3]">
                <td class="px-6 py-2" />
                <td class="py-2 pl-14 pr-6">
                  <span class="text-[10px] font-black uppercase tracking-widest text-[#64746B]">
                    {{ d.department }} — subtotal
                  </span>
                </td>
                <td class="px-6 py-2 text-right font-mono text-[12px] font-black text-[#064E3B]">
                  {{ php(bget(d)) }}
                </td>
                <td colspan="4" class="px-6 py-2 text-right font-mono text-[10px] text-[#64746B]">
                  {{ d.children.length }} sub-offices · own {{ phpM(d.own_budget ?? 0) }}
                </td>
              </tr>
            </template>
          </template>

          <tr v-if="filtered.length === 0">
            <td colspan="7" class="px-6 py-14 text-center text-sm font-semibold text-[#8FA79B]">No results found.</td>
          </tr>
        </tbody>

        <!-- Footer totals -->
        <tfoot v-if="filtered.length > 0">
          <tr class="border-t-2 border-[#DDEDE3] bg-[#F8FCF9]">
            <td colspan="2" class="px-6 py-3.5">
              <span class="text-[11px] font-black uppercase tracking-widest text-[#64746B]">Total</span>
            </td>
            <td v-for="col in COLUMNS" :key="col.key" class="px-6 py-3.5 text-right font-mono font-black text-[#064E3B]">
              {{ php(filtered.reduce((s, d) => s + col.get(d), 0)) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </SectionCard>
</template>
