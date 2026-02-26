<script setup>
import { ref, computed } from 'vue'
import AppLayout    from '@/Layouts/AppLayout.vue'
import YearSelector from '@/Components/YearSelector.vue'

const props = defineProps({
  submissions: { type: Array,  default: () => [] },
  years:       { type: Array,  default: () => [] },
  piSamples:   { type: Object, default: () => ({}) },
})

// ── State ─────────────────────────────────────────────────────
const year       = ref(props.years[props.years.length - 1] ?? 2026)
const search     = ref('')
const filterStatus = ref('All')
const expandedDept = ref(null)

// ── Derived ───────────────────────────────────────────────────
const forYear = computed(() =>
  props.submissions.filter(s => s.year === year.value)
)

const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return forYear.value.filter(s => {
    const matchQ = !q || s.department.toLowerCase().includes(q)
    const matchS = filterStatus.value === 'All' || normalizeStatus(s.status) === filterStatus.value
    return matchQ && matchS
  })
})

// ── Stats ─────────────────────────────────────────────────────
const total     = computed(() => forYear.value.length)
const approved  = computed(() => forYear.value.filter(s => isApproved(s.status)).length)
const pending   = computed(() => forYear.value.filter(s => isPending(s.status)).length)
const forRevision = computed(() => forYear.value.filter(s => isRevision(s.status)).length)
const progressPct = computed(() => total.value ? Math.round(approved.value / total.value * 100) : 0)

const totalBudget = computed(() => forYear.value.reduce((s, d) => s + (d.budget_total || 0), 0))
const totalPIs    = computed(() => forYear.value.reduce((s, d) => s + (d.pi_count || 0), 0))

const fund101 = computed(() => forYear.value.reduce((s, d) => s + (d.budget_fund_101 || 0), 0))
const fund164 = computed(() => forYear.value.reduce((s, d) => s + (d.budget_fund_164 || 0), 0))
const fund161 = computed(() => forYear.value.reduce((s, d) => s + (d.budget_fund_161 || 0), 0))
const fund163 = computed(() => forYear.value.reduce((s, d) => s + (d.budget_fund_163 || 0), 0))

// ── Status helpers ────────────────────────────────────────────
const isApproved = s => /approved|done/i.test(s)
const isPending  = s => /pending|no entries|not yet/i.test(s)
const isRevision = s => /comply|revision|ongoing|review/i.test(s)

const normalizeStatus = s => {
  if (isApproved(s)) return 'Approved'
  if (isPending(s))  return 'Pending'
  if (isRevision(s)) return 'For Revision'
  return 'Other'
}

const statusColor = s => {
  if (isApproved(s)) return 'bg-emerald-100 text-emerald-700 border-emerald-200'
  if (isPending(s))  return 'bg-amber-100 text-amber-700 border-amber-200'
  if (isRevision(s)) return 'bg-orange-100 text-orange-700 border-orange-200'
  return 'bg-gray-100 text-gray-600 border-gray-200'
}

const statusDot = s => {
  if (isApproved(s)) return 'bg-emerald-500'
  if (isPending(s))  return 'bg-amber-400'
  if (isRevision(s)) return 'bg-orange-500'
  return 'bg-gray-400'
}

// ── Formatters ────────────────────────────────────────────────
const phpM = v => v >= 1e6
  ? '₱' + (v / 1e6).toFixed(2) + 'M'
  : v > 0 ? '₱' + v.toLocaleString('en-PH') : '—'

const pct  = (v, total) => total > 0 ? (v / total * 100).toFixed(1) + '%' : '—'

const toggleDept = id => {
  expandedDept.value = expandedDept.value === id ? null : id
}
</script>

<template>
  <AppLayout>
    <template #breadcrumb>Compliance Monitor</template>
    <template #title>Compliance Monitor</template>
    <template #subtitle>WFP submission status · Fund breakdown · PRMO remarks</template>

    <div class="space-y-5">

      <!-- ── Top bar ─────────────────────────────────────────── -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2 flex-wrap">

          <!-- Status filter pills -->
          <button v-for="f in ['All','Approved','Pending','For Revision']" :key="f"
            @click="filterStatus = f"
            :class="['text-[11px] font-bold px-3 py-1.5 rounded-full border transition-all',
              filterStatus === f
                ? 'bg-[#0D2137] text-white border-[#0D2137]'
                : 'bg-white text-gray-500 border-gray-200 hover:border-[#0D2137]/30']">
            {{ f }}
            <span v-if="f !== 'All'" class="ml-1 opacity-60">
              ({{ f === 'Approved' ? approved : f === 'Pending' ? pending : forRevision }})
            </span>
          </button>

          <!-- Search -->
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400"
              fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            <input v-model="search" placeholder="Search department…"
              class="pl-8 pr-4 py-1.5 text-[12px] border border-gray-200 rounded-full w-44 bg-white focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 focus:border-[#0D2137]/40" />
          </div>
        </div>

        <YearSelector v-model="year" :years="years" />
      </div>

      <!-- ── KPI Row ─────────────────────────────────────────── -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">

        <!-- Approval progress -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <div class="flex items-start justify-between mb-3">
            <div>
              <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">Submission Progress</p>
              <p class="text-[26px] font-extrabold text-[#0D2137] leading-tight mt-1">
                {{ approved }} <span class="text-[16px] text-gray-400 font-semibold">/ {{ total }}</span>
              </p>
              <p class="text-[11px] text-gray-400 mt-0.5">departments approved for FY {{ year }}</p>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center">
              <span class="text-[16px] font-extrabold text-emerald-600">{{ progressPct }}%</span>
            </div>
          </div>

          <!-- Progress bar -->
          <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-700 bg-gradient-to-r from-emerald-500 to-emerald-400"
              :style="{ width: progressPct + '%' }" />
          </div>
          <div class="flex items-center justify-between mt-2">
            <div class="flex items-center gap-3 text-[10px] font-bold">
              <span class="flex items-center gap-1 text-emerald-600">
                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block" />
                {{ approved }} Approved
              </span>
              <span class="flex items-center gap-1 text-amber-600">
                <span class="w-2 h-2 rounded-full bg-amber-400 inline-block" />
                {{ pending }} Pending
              </span>
              <span class="flex items-center gap-1 text-orange-600">
                <span class="w-2 h-2 rounded-full bg-orange-500 inline-block" />
                {{ forRevision }} For Revision
              </span>
            </div>
          </div>
        </div>

        <!-- Total budget -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400 mb-1">Total Budget</p>
          <p class="text-[22px] font-extrabold text-[#0D2137] leading-tight">{{ phpM(totalBudget) }}</p>
          <p class="text-[11px] text-gray-400 mt-0.5">FY {{ year }} verified total</p>
          <div class="mt-3 space-y-1.5">
            <div v-for="[label, val, color] in [
              ['Fund 101', fund101, 'bg-[#0D2137]'],
              ['Fund 164', fund164, 'bg-[#C9A84C]'],
              ['Fund 161', fund161, 'bg-[#1A5276]'],
              ['Fund 163', fund163, 'bg-emerald-500'],
            ]" :key="label" class="flex items-center gap-2">
              <div :class="['w-1.5 h-1.5 rounded-full shrink-0', color]" />
              <span class="text-[10px] text-gray-500 w-14">{{ label }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-1 overflow-hidden">
                <div :class="['h-full rounded-full', color]"
                  :style="{ width: totalBudget > 0 ? (val/totalBudget*100).toFixed(1)+'%' : '0%' }" />
              </div>
              <span class="text-[10px] font-bold text-gray-600 w-14 text-right">{{ phpM(val) }}</span>
            </div>
          </div>
        </div>

        <!-- PI count -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400 mb-1">Performance Indicators</p>
          <p class="text-[22px] font-extrabold text-[#C9A84C] leading-tight">{{ totalPIs.toLocaleString() }}</p>
          <p class="text-[11px] text-gray-400 mt-0.5">total PIs across all offices</p>
          <div class="mt-3 bg-[#C9A84C]/8 border border-[#C9A84C]/15 rounded-xl px-3 py-2.5">
            <p class="text-[11px] font-bold text-[#8B6914]">
              Avg {{ forYear.length ? Math.round(totalPIs / forYear.length) : 0 }} PIs / office
            </p>
            <p class="text-[10px] text-[#8B6914]/70 mt-0.5">
              ₱{{ forYear.length && totalPIs > 0
                ? Math.round(totalBudget / totalPIs).toLocaleString()
                : 0 }} budget per PI
            </p>
          </div>
        </div>
      </div>

      <!-- ── Fund Breakdown Summary Bar ─────────────────────── -->
      <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4">
        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400 mb-3">
          Budget by Fund Cluster — FY {{ year }}
        </p>
        <div class="flex rounded-xl overflow-hidden h-6">
          <div v-for="[label, val, color, text] in [
            ['Fund 101', fund101, 'bg-[#0D2137]', 'text-white'],
            ['Fund 164', fund164, 'bg-[#C9A84C]', 'text-[#0D2137]'],
            ['Fund 161', fund161, 'bg-[#1A5276]', 'text-white'],
            ['Fund 163', fund163, 'bg-emerald-500','text-white'],
          ]" :key="label"
            :class="['flex items-center justify-center transition-all overflow-hidden', color, text]"
            :style="{ width: totalBudget > 0 ? (val/totalBudget*100).toFixed(1)+'%' : '0%' }"
            :title="`${label}: ${phpM(val)} (${pct(val, totalBudget)})`">
            <span v-if="val/totalBudget > 0.08" class="text-[10px] font-extrabold whitespace-nowrap px-1">
              {{ label }} {{ pct(val, totalBudget) }}
            </span>
          </div>
        </div>
        <div class="flex items-center gap-4 mt-2">
          <div v-for="[label, val, dot] in [
            ['Fund 101 (NEP/GAA)', fund101, 'bg-[#0D2137]'],
            ['Fund 164 (Fiduciary)', fund164, 'bg-[#C9A84C]'],
            ['Fund 161', fund161, 'bg-[#1A5276]'],
            ['Fund 163', fund163, 'bg-emerald-500'],
          ]" :key="label" class="flex items-center gap-1.5">
            <div :class="['w-2 h-2 rounded-full', dot]" />
            <span class="text-[10px] text-gray-500">{{ label }}:
              <strong class="text-gray-700">{{ phpM(val) }}</strong>
            </span>
          </div>
        </div>
      </div>

      <!-- ── Department Table ───────────────────────────────── -->
      <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        <!-- Table header -->
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h3 class="text-[13px] font-extrabold text-[#0D2137]">Department Submissions</h3>
            <p class="text-[11px] text-gray-400">{{ filtered.length }} of {{ total }} departments · FY {{ year }}</p>
          </div>
          <div v-if="filtered.length !== total"
            class="text-[11px] font-bold text-[#C9A84C] bg-[#C9A84C]/10 border border-[#C9A84C]/20 px-3 py-1 rounded-full">
            Filtered: {{ filtered.length }}
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-[12px]">
            <thead>
              <tr class="border-b border-gray-100 bg-gray-50/50">
                <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-8">#</th>
                <th class="text-left px-5 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department</th>
                <th class="text-center px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Status</th>
                <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Total Budget</th>
                <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Fund 101</th>
                <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Fund 164</th>
                <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Fund 161</th>
                <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">PIs</th>
                <th class="text-left px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Remarks</th>
                <th class="w-10 px-4 py-3" />
              </tr>
            </thead>
            <tbody>
              <template v-for="dept in filtered" :key="dept.id">

                <!-- Main row -->
                <tr class="border-b border-gray-50 hover:bg-[#0D2137]/[0.018] transition-colors group"
                  :class="expandedDept === dept.id ? 'bg-[#0D2137]/[0.025]' : ''">

                  <td class="px-5 py-3.5 text-gray-300 font-mono font-bold text-[10px]">
                    {{ dept.no || '—' }}
                  </td>

                  <td class="px-5 py-3.5">
                    <span class="font-semibold text-[#0D2137] group-hover:text-[#1A5276] transition-colors">
                      {{ dept.department }}
                    </span>
                  </td>

                  <td class="px-4 py-3.5 text-center">
                    <span :class="['inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-1 rounded-full border', statusColor(dept.status)]">
                      <span :class="['w-1.5 h-1.5 rounded-full', statusDot(dept.status)]" />
                      {{ normalizeStatus(dept.status) }}
                    </span>
                  </td>

                  <td class="px-4 py-3.5 text-right font-mono font-bold text-[#0D2137]">
                    {{ phpM(dept.budget_total) }}
                  </td>

                  <td class="px-4 py-3.5 text-right text-gray-500 font-mono text-[11px]">
                    {{ dept.budget_fund_101 > 0 ? phpM(dept.budget_fund_101) : '—' }}
                  </td>
                  <td class="px-4 py-3.5 text-right text-gray-500 font-mono text-[11px]">
                    {{ dept.budget_fund_164 > 0 ? phpM(dept.budget_fund_164) : '—' }}
                  </td>
                  <td class="px-4 py-3.5 text-right text-gray-500 font-mono text-[11px]">
                    {{ dept.budget_fund_161 > 0 ? phpM(dept.budget_fund_161) : '—' }}
                  </td>

                  <td class="px-4 py-3.5 text-right">
                    <span v-if="dept.pi_count > 0"
                      class="bg-[#C9A84C]/10 text-[#8B6914] text-[10px] font-bold px-2 py-0.5 rounded-full border border-[#C9A84C]/20">
                      {{ dept.pi_count }}
                    </span>
                    <span v-else class="text-gray-300 text-[11px]">—</span>
                  </td>

                  <td class="px-4 py-3.5 max-w-[220px]">
                    <p v-if="dept.remarks" class="text-[11px] text-gray-500 truncate" :title="dept.remarks">
                      {{ dept.remarks }}
                    </p>
                    <span v-else class="text-gray-300 text-[11px]">—</span>
                  </td>

                  <!-- Expand button -->
                  <td class="px-4 py-3.5">
                    <button v-if="dept.pi_count > 0"
                      @click="toggleDept(dept.id)"
                      class="w-6 h-6 rounded-lg flex items-center justify-center border border-gray-200 hover:border-[#C9A84C]/40 hover:bg-[#C9A84C]/10 transition-all">
                      <svg :class="['w-3 h-3 text-gray-400 transition-transform duration-200',
                        expandedDept === dept.id ? 'rotate-180 text-[#C9A84C]' : '']"
                        fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <polyline points="6 9 12 15 18 9"/>
                      </svg>
                    </button>
                  </td>
                </tr>

                <!-- ── PI Detail Drawer ──────────────────────── -->
                <tr v-if="expandedDept === dept.id" class="bg-[#F8FAFC]">
                  <td colspan="10" class="px-5 py-0">
                    <div class="py-4 border-t border-dashed border-[#C9A84C]/30">

                      <!-- PI drawer header -->
                      <div class="flex items-center gap-2 mb-3">
                        <div class="w-5 h-5 rounded-lg bg-[#C9A84C]/15 border border-[#C9A84C]/25 flex items-center justify-center">
                          <svg class="w-3 h-3 text-[#C9A84C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                          </svg>
                        </div>
                        <p class="text-[11px] font-extrabold text-[#0D2137] uppercase tracking-wider">
                          Performance Indicators — {{ dept.department }}
                        </p>
                        <span class="text-[10px] bg-[#C9A84C]/10 text-[#8B6914] border border-[#C9A84C]/20 px-2 py-0.5 rounded-full font-bold">
                          {{ dept.pi_count }} PIs
                        </span>
                      </div>

                      <!-- PI list from piSamples prop -->
                      <div class="space-y-1.5 max-h-64 overflow-y-auto pr-1">
                        <div v-for="pi in (piSamples[dept.id] ?? [])" :key="pi.id"
                          class="flex gap-3 bg-white rounded-xl border border-gray-100 px-4 py-2.5 hover:border-[#C9A84C]/25 transition-colors">
                          <span class="text-[10px] font-mono font-bold text-[#C9A84C] shrink-0 mt-0.5 w-16">
                            {{ pi.code || '#' + pi.seq }}
                          </span>
                          <div class="flex-1 min-w-0">
                            <p class="text-[12px] font-semibold text-[#0D2137] leading-snug">{{ pi.description }}</p>
                            <div class="flex items-center gap-3 mt-1">
                              <span v-if="pi.reference_source"
                                class="text-[10px] text-gray-400">{{ pi.reference_source }}</span>
                              <span v-if="pi.target"
                                class="text-[10px] font-bold text-[#1A5276] bg-blue-50 border border-blue-100 px-1.5 py-0.5 rounded-md">
                                Target: {{ pi.target }}
                              </span>
                            </div>
                          </div>
                        </div>

                        <!-- Loading state -->
                        <div v-if="!piSamples[dept.id]"
                          class="text-center py-6 text-[11px] text-gray-400">
                          PI details will load here
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>

              </template>

              <!-- Empty state -->
              <tr v-if="filtered.length === 0">
                <td colspan="10" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center gap-2">
                    <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center">
                      <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
                      </svg>
                    </div>
                    <p class="text-[13px] font-bold text-gray-400">
                      {{ forYear.length === 0 ? `No data for FY ${year} yet — upload a file first.` : 'No departments match your filter.' }}
                    </p>
                  </div>
                </td>
              </tr>
            </tbody>

            <!-- Totals footer -->
            <tfoot v-if="filtered.length > 0">
              <tr class="border-t-2 border-gray-200 bg-gray-50/80">
                <td colspan="3" class="px-5 py-3.5">
                  <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-500">
                    Total ({{ filtered.length }} shown)
                  </span>
                </td>
                <td class="px-4 py-3.5 text-right font-mono font-extrabold text-[#0D2137]">
                  {{ phpM(filtered.reduce((s, d) => s + (d.budget_total || 0), 0)) }}
                </td>
                <td class="px-4 py-3.5 text-right font-mono font-bold text-gray-600 text-[11px]">
                  {{ phpM(filtered.reduce((s, d) => s + (d.budget_fund_101 || 0), 0)) }}
                </td>
                <td class="px-4 py-3.5 text-right font-mono font-bold text-gray-600 text-[11px]">
                  {{ phpM(filtered.reduce((s, d) => s + (d.budget_fund_164 || 0), 0)) }}
                </td>
                <td class="px-4 py-3.5 text-right font-mono font-bold text-gray-600 text-[11px]">
                  {{ phpM(filtered.reduce((s, d) => s + (d.budget_fund_161 || 0), 0)) }}
                </td>
                <td class="px-4 py-3.5 text-right font-bold text-[#0D2137]">
                  {{ filtered.reduce((s, d) => s + (d.pi_count || 0), 0) }}
                </td>
                <td colspan="2" class="px-4 py-3.5" />
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
