<script setup>
/**
 * Budget/RankingTab.vue
 * Expandable budget ranking table.
 * Dynamic across any number of fiscal years. No 2024/2025/2026 graveyard here.
 */
import { ref } from 'vue'
import SectionCard from '@/Components/SectionCard.vue'
import { useFormatters } from '@/composables/useFormatters'

defineProps({
  filteredTree: { type: Array, default: () => [] },
  expandedRows: { type: Array, default: () => [] },
  parentRows: { type: Array, default: () => [] },
  years: { type: Array, default: () => [] },
  latestYear: { type: [Number, String, null], default: null },
  previousYear: { type: [Number, String, null], default: null },
  changeField: { type: String, default: null },
})

const emit = defineEmits(['toggle', 'expand-all', 'collapse-all', 'update:search'])

const { phpM, pct } = useFormatters()
const search = ref('')
const trendColor = (value) => value == null ? 'text-gray-300' : value > 0 ? 'text-emerald-600' : value < 0 ? 'text-red-500' : 'text-gray-400'
const trendBg = (value) => value == null ? '' : value > 0 ? 'bg-emerald-50 border-emerald-100' : value < 0 ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-100'
</script>

<template>
  <SectionCard
    :title="`Budget Ranking — ${parentRows.length} departments`"
    :subtitle="`Click a row to expand sub-offices · FY ${years[0] ?? '—'} / ${latestYear ?? '—'}`"
    :noPad="true"
  >
    <template #actions>
      <div class="flex items-center gap-2">
        <button
          @click="emit('expand-all')"
          class="text-[11px] font-bold text-[#064E3B] hover:text-[#C9A84C] transition-colors px-2 py-1 rounded-lg hover:bg-gray-50"
        >
          Expand All
        </button>
        <span class="text-gray-200 text-xs">|</span>
        <button
          @click="emit('collapse-all')"
          class="text-[11px] font-bold text-gray-400 hover:text-gray-600 transition-colors px-2 py-1 rounded-lg hover:bg-gray-50"
        >
          Collapse
        </button>

        <div class="relative ml-1">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8" />
            <path d="M21 21l-4.35-4.35" />
          </svg>
          <input
            v-model="search"
            @input="emit('update:search', search)"
            type="text"
            placeholder="Search office…"
            class="pl-8 pr-4 py-2 text-[12px] border border-gray-200 rounded-xl w-48 focus:outline-none focus:ring-2 focus:ring-[#064E3B]/20 bg-gray-50/50 text-gray-600 placeholder-gray-300"
          />
        </div>
      </div>
    </template>

    <div class="overflow-x-auto">
      <table class="w-full text-[12px]">
        <thead>
          <tr class="border-b-2 border-gray-100">
            <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-8">#</th>
            <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department / Office</th>
            <th
              v-for="year in years"
              :key="year"
              class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400"
            >
              FY {{ year }}
            </th>
            <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
              {{ previousYear ?? '—' }}→{{ latestYear ?? '—' }}
            </th>
            <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">F101</th>
            <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">F164</th>
          </tr>
        </thead>

        <tbody>
          <template v-for="(row, index) in filteredTree" :key="(row.office_key || row.department) + (row._isChild ? '_child' : '')">
            <tr
              v-if="!row._isChild"
              @click="row._hasChildren ? emit('toggle', row.department) : null"
              :class="[
                'border-b border-gray-100 transition-colors',
                row._hasChildren ? 'cursor-pointer hover:bg-emerald-50/40' : 'hover:bg-gray-50/60',
                expandedRows.includes(row.department) ? 'bg-[#064E3B]/[0.03]' : '',
              ]"
            >
              <td class="px-5 py-3 text-gray-300 font-mono text-[10px] font-bold">
                {{ String(index + 1).padStart(2, '0') }}
              </td>

              <td class="px-5 py-3">
                <div class="flex items-center gap-2">
                  <span
                    v-if="row._hasChildren"
                    :class="[
                      'w-4 h-4 flex items-center justify-center transition-transform shrink-0 text-gray-300',
                      expandedRows.includes(row.department) ? 'rotate-90' : '',
                    ]"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path d="M9 18l6-6-6-6" />
                    </svg>
                  </span>
                  <span v-else class="w-4 shrink-0" />

                  <span class="font-bold text-[#064E3B] text-[12px]">{{ row.department }}</span>
                  <span
                    v-if="row._hasChildren"
                    class="text-[9px] font-bold text-emerald-400 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded-full"
                  >
                    {{ row._childCount }} sub
                  </span>
                </div>
              </td>

              <td v-for="year in years" :key="year" class="px-4 py-3 text-right">
                <template v-if="row._hasChildren && row[`budget_${year}`] > 0">
                  <p :class="['font-mono font-bold text-[11px]', year === latestYear ? 'text-[#064E3B] text-[12px]' : 'text-gray-500']">
                    {{ phpM(row[`budget_${year}`]) }}
                  </p>
                  <p v-if="row[`own_${year}`] > 0" class="font-mono text-[9px] text-gray-300 mt-0.5">
                    own {{ phpM(row[`own_${year}`]) }}
                  </p>
                </template>
                <span v-else :class="['font-mono text-[11px]', year === latestYear ? 'font-bold text-[#064E3B] text-[12px]' : 'text-gray-400']">
                  {{ row[`budget_${year}`] ? phpM(row[`budget_${year}`]) : '—' }}
                </span>
              </td>

              <td class="px-4 py-3 text-right">
                <span
                  v-if="changeField && row[changeField] != null"
                  :class="['text-[11px] font-bold px-2 py-0.5 rounded-full border', trendBg(row[changeField]), trendColor(row[changeField])]"
                >
                  {{ pct(row[changeField]) }}
                </span>
                <span v-else class="text-gray-300 text-[11px]">—</span>
              </td>

              <td class="px-4 py-3 text-right font-mono text-gray-400 text-[11px]">
                {{ row[`f101_${latestYear}`] > 0 ? phpM(row[`f101_${latestYear}`]) : '—' }}
              </td>
              <td class="px-4 py-3 text-right font-mono text-gray-400 text-[11px]">
                {{ row[`f164_${latestYear}`] > 0 ? phpM(row[`f164_${latestYear}`]) : '—' }}
              </td>
            </tr>

            <tr
              v-if="row._isChild && expandedRows.includes(row._parentName)"
              class="border-b border-gray-50 bg-slate-50/60 hover:bg-emerald-50/20 transition-colors"
            >
              <td class="px-5 py-2.5" />
              <td class="py-2.5 pl-14 pr-4">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-px bg-gray-300" />
                  <div class="w-1 h-1 rounded-full bg-gray-300" />
                  <span class="font-medium text-gray-600 text-[12px]">{{ row.department }}</span>
                </div>
              </td>

              <td
                v-for="year in years"
                :key="year"
                :class="[
                  'px-4 py-2.5 text-right font-mono text-[11px]',
                  year === latestYear ? 'font-semibold text-gray-600 text-[12px]' : 'text-gray-400',
                ]"
              >
                {{ row[`budget_${year}`] ? phpM(row[`budget_${year}`]) : '—' }}
              </td>

              <td class="px-4 py-2.5 text-right">
                <span
                  v-if="changeField && row[changeField] != null"
                  :class="['text-[10px] font-bold px-1.5 py-0.5 rounded-full border', trendBg(row[changeField]), trendColor(row[changeField])]"
                >
                  {{ pct(row[changeField]) }}
                </span>
              </td>

              <td class="px-4 py-2.5 text-right font-mono text-gray-400 text-[11px]">
                {{ row[`f101_${latestYear}`] > 0 ? phpM(row[`f101_${latestYear}`]) : '—' }}
              </td>
              <td class="px-4 py-2.5 text-right font-mono text-gray-400 text-[11px]">
                {{ row[`f164_${latestYear}`] > 0 ? phpM(row[`f164_${latestYear}`]) : '—' }}
              </td>
            </tr>
          </template>

          <tr v-if="filteredTree.length === 0">
            <td :colspan="years.length + 5" class="px-5 py-12 text-center text-gray-300 text-sm">
              No results found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </SectionCard>
</template>
