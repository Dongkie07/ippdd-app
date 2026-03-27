<script setup>
/**
 * Budget/RankingTab.vue
 * ─────────────────────────────────────────
 * Expandable budget ranking table — all departments,
 * FY 2024 / 2025 / 2026 side by side with YoY % change badges.
 * Click a parent row to expand its sub-offices.
 *
 * Props:
 *   filteredTree  Array — flat list of parent + child rows (from Budget.vue)
 *   expandedRows  Array — list of currently expanded dept names
 *   parentRows    Array — only parent rows (for expand-all)
 */
import SectionCard from '@/Components/SectionCard.vue'
import { ref } from 'vue'
import { useFormatters } from '@/composables/useFormatters'
import { useTableSort }  from '@/composables/useTableSort'

defineProps({
  filteredTree: { type: Array, default: () => [] },
  expandedRows: { type: Array, default: () => [] },
  parentRows:   { type: Array, default: () => [] },
})
const emit = defineEmits(['toggle', 'expand-all', 'collapse-all', 'update:search'])

const { phpM, pct } = useFormatters()
const { setSort, sortIcon } = useTableSort('budget_2026', 'desc')
const search = ref('')

const trendColor = v => v == null ? 'text-gray-300' : v > 0 ? 'text-emerald-600' : v < 0 ? 'text-red-500' : 'text-gray-400'
const trendBg    = v => v == null ? '' : v > 0 ? 'bg-emerald-50 border-emerald-100' : v < 0 ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-100'
</script>

<template>
  <SectionCard
    :title="`Budget Ranking — ${parentRows.length} departments`"
    subtitle="Click a row to expand sub-offices · FY 2024 / 2025 / 2026"
    :noPad="true">
    <template #actions>
      <div class="flex items-center gap-2">
        <button @click="emit('expand-all')" class="text-[11px] font-bold text-[#0D2137] hover:text-[#C9A84C] transition-colors px-2 py-1 rounded-lg hover:bg-gray-50">Expand All</button>
        <span class="text-gray-200 text-xs">|</span>
        <button @click="emit('collapse-all')" class="text-[11px] font-bold text-gray-400 hover:text-gray-600 transition-colors px-2 py-1 rounded-lg hover:bg-gray-50">Collapse</button>
        <div class="relative ml-1">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
          </svg>
          <input v-model="search" @input="emit('update:search', search)"
            type="text" placeholder="Search office…"
            class="pl-8 pr-4 py-2 text-[12px] border border-gray-200 rounded-xl w-48
                   focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 bg-gray-50/50
                   text-gray-600 placeholder-gray-300" />
        </div>
      </div>
    </template>

    <div class="overflow-x-auto">
      <table class="w-full text-[12px]">
        <thead>
          <tr class="border-b-2 border-gray-100">
            <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-8">#</th>
            <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department / Office</th>
            <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">FY 2024</th>
            <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">FY 2025</th>
            <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">FY 2026</th>
            <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">25→26</th>
            <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">F101</th>
            <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">F164</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(r, i) in filteredTree" :key="r.department + (r._isChild ? '_child' : '')">

            <!-- Parent row -->
            <tr v-if="!r._isChild"
              @click="r._hasChildren ? emit('toggle', r.department) : null"
              :class="['border-b border-gray-100 transition-colors',
                r._hasChildren ? 'cursor-pointer hover:bg-blue-50/40' : 'hover:bg-gray-50/60',
                expandedRows.includes(r.department) ? 'bg-[#0D2137]/[0.03]' : '']">
              <td class="px-5 py-3 text-gray-300 font-mono text-[10px] font-bold">{{ String(i+1).padStart(2,'0') }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-2">
                  <span v-if="r._hasChildren"
                    :class="['w-4 h-4 flex items-center justify-center transition-transform shrink-0 text-gray-300',
                      expandedRows.includes(r.department) ? 'rotate-90' : '']">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                  </span>
                  <span v-else class="w-4 shrink-0" />
                  <span class="font-bold text-[#0D2137] text-[12px]">{{ r.department }}</span>
                  <span v-if="r._hasChildren" class="text-[9px] font-bold text-blue-400 bg-blue-50 border border-blue-100 px-1.5 py-0.5 rounded-full">
                    {{ r._childCount }} sub
                  </span>
                </div>
              </td>
              <!-- FY 2024 -->
              <td class="px-4 py-3 text-right">
                <template v-if="r._hasChildren && r._combined_2024 > 0">
                  <p class="font-mono font-bold text-gray-500 text-[11px]">{{ phpM(r._combined_2024) }}</p>
                  <p v-if="r._own_2024 > 0" class="font-mono text-[9px] text-gray-300 mt-0.5">own {{ phpM(r._own_2024) }}</p>
                </template>
                <span v-else class="font-mono text-gray-400 text-[11px]">{{ r.budget_2024 ? phpM(r.budget_2024) : '—' }}</span>
              </td>
              <!-- FY 2025 -->
              <td class="px-4 py-3 text-right">
                <template v-if="r._hasChildren && r._combined_2025 > 0">
                  <p class="font-mono font-bold text-gray-600 text-[11px]">{{ phpM(r._combined_2025) }}</p>
                  <p v-if="r._own_2025 > 0" class="font-mono text-[9px] text-gray-300 mt-0.5">own {{ phpM(r._own_2025) }}</p>
                </template>
                <span v-else class="font-mono text-gray-500 text-[11px]">{{ r.budget_2025 ? phpM(r.budget_2025) : '—' }}</span>
              </td>
              <!-- FY 2026 -->
              <td class="px-4 py-3 text-right">
                <template v-if="r._hasChildren && r._combined_2026 > 0">
                  <p class="font-mono font-bold text-[#0D2137] text-[12px]">{{ phpM(r._combined_2026) }}</p>
                  <p v-if="r._own_2026 > 0" class="font-mono text-[9px] text-gray-400 mt-0.5">own {{ phpM(r._own_2026) }}</p>
                </template>
                <span v-else class="font-mono font-bold text-[#0D2137] text-[12px]">{{ r.budget_2026 ? phpM(r.budget_2026) : '—' }}</span>
              </td>
              <!-- 25→26 change -->
              <td class="px-4 py-3 text-right">
                <span v-if="r.chg_25_26 != null" :class="['text-[11px] font-bold px-2 py-0.5 rounded-full border', trendBg(r.chg_25_26), trendColor(r.chg_25_26)]">
                  {{ pct(r.chg_25_26) }}
                </span>
                <span v-else class="text-gray-300 text-[11px]">—</span>
              </td>
              <td class="px-4 py-3 text-right font-mono text-gray-400 text-[11px]">{{ r.f101_2026 > 0 ? phpM(r.f101_2026) : '—' }}</td>
              <td class="px-4 py-3 text-right font-mono text-gray-400 text-[11px]">{{ r.f164_2026 > 0 ? phpM(r.f164_2026) : '—' }}</td>
            </tr>

            <!-- Child row -->
            <tr v-if="r._isChild && expandedRows.includes(r._parentName)"
              class="border-b border-gray-50 bg-slate-50/60 hover:bg-blue-50/20 transition-colors">
              <td class="px-5 py-2.5" />
              <td class="py-2.5 pl-14 pr-4">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-px bg-gray-300" />
                  <div class="w-1 h-1 rounded-full bg-gray-300" />
                  <span class="font-medium text-gray-600 text-[12px]">{{ r.department }}</span>
                </div>
              </td>
              <td class="px-4 py-2.5 text-right font-mono text-gray-300 text-[11px]">{{ r.budget_2024 ? phpM(r.budget_2024) : '—' }}</td>
              <td class="px-4 py-2.5 text-right font-mono text-gray-400 text-[11px]">{{ r.budget_2025 ? phpM(r.budget_2025) : '—' }}</td>
              <td class="px-4 py-2.5 text-right font-mono font-semibold text-gray-600 text-[12px]">{{ r.budget_2026 ? phpM(r.budget_2026) : '—' }}</td>
              <td class="px-4 py-2.5 text-right">
                <span v-if="r.chg_25_26 != null" :class="['text-[10px] font-bold px-1.5 py-0.5 rounded-full border', trendBg(r.chg_25_26), trendColor(r.chg_25_26)]">
                  {{ pct(r.chg_25_26) }}
                </span>
              </td>
              <td class="px-4 py-2.5 text-right font-mono text-gray-400 text-[11px]">{{ r.f101_2026 > 0 ? phpM(r.f101_2026) : '—' }}</td>
              <td class="px-4 py-2.5 text-right font-mono text-gray-400 text-[11px]">{{ r.f164_2026 > 0 ? phpM(r.f164_2026) : '—' }}</td>
            </tr>

          </template>
          <tr v-if="filteredTree.length === 0">
            <td colspan="8" class="px-5 py-12 text-center text-gray-300 text-sm">No results found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </SectionCard>
</template>
