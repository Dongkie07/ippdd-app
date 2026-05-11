<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useOffices } from './Offices/useOffices'

const props = defineProps({
  offices: { type: Array, default: () => [] },
  needsMigration: { type: Boolean, default: false },
  unlinkedSubmissionCount: { type: Number, default: 0 },
})

const {
  query,
  saving,
  feedback,
  officeForm,
  historyForm,
  formMode,
  filteredOffices,
  selectedOffice,
  selectedOfficeId,
  selectedTimeline,
  selectedPreview,
  officeStats,
  registryHealth,
  hasSearchQuery,
  clearSearch,
  openAddOffice,
  selectOffice,
  saveOffice,
  saveHistory,
  removeHistory,
  syncOffices,
} = useOffices(props)
</script>

<template>
  <AppLayout>
    <template #breadcrumb>
      <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#168A4A]">Management</p>
    </template>
    <template #title>Office Registry</template>
    <template #subtitle>Manage latest office names without losing old yearly labels</template>

    <Transition name="toast">
      <div
        v-if="feedback"
        :class="[
          'fixed right-6 top-24 z-50 rounded-2xl px-4 py-3 text-[13px] font-bold shadow-2xl ring-1 backdrop-blur-xl',
          feedback.type === 'error'
            ? 'bg-red-50/95 text-red-700 ring-red-100'
            : 'bg-emerald-50/95 text-emerald-700 ring-emerald-100',
        ]"
      >
        {{ feedback.message }}
      </div>
    </Transition>

    <div class="space-y-5">
      <section class="registry-hero relative overflow-hidden rounded-[2rem] bg-[#042F24] p-6 text-white shadow-2xl shadow-[#064E3B]/15">
        <div class="absolute inset-0 opacity-40">
          <div class="absolute -right-20 top-0 h-72 w-72 rounded-full bg-[#53D28C]/35 blur-3xl" />
          <div class="absolute -bottom-24 left-1/3 h-72 w-72 rounded-full bg-white/10 blur-3xl" />
        </div>
        <div class="registry-grid pointer-events-none absolute inset-0" />

        <div class="relative grid gap-6 xl:grid-cols-[1.3fr_.7fr]">
          <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-[11px] font-black uppercase tracking-[0.16em] text-[#B7F4CE]">
              <span class="h-2 w-2 rounded-full bg-[#53D28C] shadow-[0_0_16px_rgba(83,210,140,.9)]" />
              Stable Office Identity
            </div>
            <h2 class="mt-5 max-w-3xl text-3xl font-black tracking-tight md:text-4xl">
              Rename-proof reports with visible office history
            </h2>
            <p class="mt-3 max-w-2xl text-sm font-medium leading-7 text-[#DDFBE8]/75">
              Main charts use the latest office name, while by-year reports can still show the exact historical name used in that period.
              It is the rare administrative compromise that does not immediately set the database on fire.
            </p>

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
              <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-xl">
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#B7F4CE]/70">Step 1</p>
                <p class="mt-2 text-[13px] font-black">Choose office identity</p>
                <p class="mt-1 text-[11px] font-semibold text-[#DDFBE8]/65">IPPDD stays the same internally.</p>
              </div>
              <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-xl">
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#B7F4CE]/70">Step 2</p>
                <p class="mt-2 text-[13px] font-black">Add yearly names</p>
                <p class="mt-1 text-[11px] font-semibold text-[#DDFBE8]/65">Old labels remain traceable.</p>
              </div>
              <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-xl">
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#B7F4CE]/70">Step 3</p>
                <p class="mt-2 text-[13px] font-black">Charts adjust cleanly</p>
                <p class="mt-1 text-[11px] font-semibold text-[#DDFBE8]/65">No duplicate fake offices.</p>
              </div>
            </div>
          </div>

          <div class="rounded-[1.5rem] border border-white/10 bg-white/10 p-5 backdrop-blur-xl">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#B7F4CE]/70">Registry Health</p>
                <p class="mt-1 text-3xl font-black">{{ registryHealth.syncRate }}%</p>
              </div>
              <button
                class="rounded-2xl bg-white px-4 py-2 text-[12px] font-black text-[#064E3B] shadow-lg shadow-black/10 transition-all hover:-translate-y-0.5 hover:bg-[#ECFDF3] disabled:opacity-50"
                :disabled="saving"
                @click="syncOffices"
              >
                {{ saving ? 'Syncing…' : 'Sync WFP Rows' }}
              </button>
            </div>

            <div class="mt-5 space-y-4">
              <div>
                <div class="flex justify-between text-[11px] font-bold text-[#DDFBE8]/75">
                  <span>Office history coverage</span>
                  <span>{{ registryHealth.historyRate }}%</span>
                </div>
                <div class="mt-2 h-2 rounded-full bg-white/10">
                  <div class="h-2 rounded-full bg-[#53D28C] transition-all duration-700" :style="{ width: `${registryHealth.historyRate}%` }" />
                </div>
              </div>
              <div>
                <div class="flex justify-between text-[11px] font-bold text-[#DDFBE8]/75">
                  <span>Active office rate</span>
                  <span>{{ registryHealth.activeRate }}%</span>
                </div>
                <div class="mt-2 h-2 rounded-full bg-white/10">
                  <div class="h-2 rounded-full bg-[#B7F4CE] transition-all duration-700" :style="{ width: `${registryHealth.activeRate}%` }" />
                </div>
              </div>
              <div class="rounded-2xl border border-white/10 bg-black/10 p-3">
                <p class="text-[11px] font-bold text-[#DDFBE8]/70">Unlinked WFP rows</p>
                <p class="mt-1 text-2xl font-black">{{ unlinkedSubmissionCount }}</p>
                <p class="mt-1 text-[11px] font-semibold text-[#DDFBE8]/55">Lower is better. Revolutionary, I know.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div v-if="needsMigration" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-[13px] font-semibold text-amber-800 shadow-sm">
        Run <span class="font-mono">php artisan migrate</span> first so the office registry tables exist.
      </div>

      <div class="grid gap-4 md:grid-cols-4">
        <div class="stat-card rounded-2xl bg-white p-4 shadow-sm ring-1 ring-[#DDEDE3]">
          <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#8FA79B]">Total Offices</p>
          <p class="mt-2 text-2xl font-black text-[#064E3B]">{{ officeStats.total }}</p>
          <p class="mt-1 text-[11px] font-semibold text-[#8FA79B]">Stable identities</p>
        </div>
        <div class="stat-card rounded-2xl bg-white p-4 shadow-sm ring-1 ring-[#DDEDE3]">
          <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#8FA79B]">Active Offices</p>
          <p class="mt-2 text-2xl font-black text-[#064E3B]">{{ officeStats.active }}</p>
          <p class="mt-1 text-[11px] font-semibold text-[#8FA79B]">Currently used</p>
        </div>
        <div class="stat-card rounded-2xl bg-white p-4 shadow-sm ring-1 ring-[#DDEDE3]">
          <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#8FA79B]">Name Records</p>
          <p class="mt-2 text-2xl font-black text-[#064E3B]">{{ officeStats.aliases }}</p>
          <p class="mt-1 text-[11px] font-semibold text-[#8FA79B]">Old and current labels</p>
        </div>
        <div class="stat-card rounded-2xl bg-white p-4 shadow-sm ring-1 ring-[#DDEDE3]">
          <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#8FA79B]">Inactive</p>
          <p class="mt-2 text-2xl font-black text-[#064E3B]">{{ officeStats.inactive }}</p>
          <p class="mt-1 text-[11px] font-semibold text-[#8FA79B]">Retained for history</p>
        </div>
      </div>

      <div class="grid gap-5 xl:grid-cols-[1.05fr_.95fr]">
        <section class="overflow-hidden rounded-[1.5rem] bg-white shadow-sm ring-1 ring-[#DDEDE3]">
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#EEF5F0] p-4">
            <div>
              <h3 class="text-[15px] font-black text-[#064E3B]">Office List</h3>
              <p class="text-[11px] font-semibold text-[#8FA79B]">Click an office to edit it. Search also checks old names and acronyms.</p>
            </div>
            <div class="relative w-full sm:w-80">
              <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#8FA79B]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="7" /><path d="M21 21l-4.3-4.3" />
              </svg>
              <input
                v-model="query"
                type="search"
                placeholder="Search office, acronym, old name..."
                class="w-full rounded-2xl border border-[#DDEDE3] bg-[#F8FCF9] py-2 pl-9 pr-10 text-[13px] font-semibold text-[#064E3B] outline-none transition-all focus:bg-white focus:ring-2 focus:ring-[#064E3B]/20"
              />
              <button
                v-if="hasSearchQuery"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full px-2 py-1 text-[10px] font-black text-[#8FA79B] hover:bg-[#ECFDF3] hover:text-[#064E3B]"
                @click="clearSearch"
              >
                Clear
              </button>
            </div>
          </div>

          <div class="max-h-[590px] overflow-y-auto p-3">
            <TransitionGroup v-if="filteredOffices.length" name="list" tag="div" class="space-y-2">
              <button
                v-for="office in filteredOffices"
                :key="office.id"
                :class="[
                  'group w-full rounded-2xl border bg-white p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-[#B7F4CE] hover:shadow-lg hover:shadow-[#064E3B]/5',
                  selectedOfficeId === office.id ? 'border-[#168A4A] ring-4 ring-[#53D28C]/15' : 'border-[#E6F1EA]',
                ]"
                @click="selectOffice(office)"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="flex items-center gap-2">
                      <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-[#ECFDF3] text-[11px] font-black text-[#064E3B] transition-all group-hover:scale-105">
                        {{ office.acronym || 'OF' }}
                      </span>
                      <div class="min-w-0">
                        <p class="truncate text-[13px] font-black text-[#064E3B]">{{ office.current_name }}</p>
                        <p class="mt-1 text-[11px] font-semibold text-[#8FA79B]">
                          {{ office.office_key }} · {{ office.histories?.length ?? 0 }} name records
                        </p>
                      </div>
                    </div>
                  </div>
                  <span :class="[
                    'rounded-full px-2.5 py-1 text-[10px] font-black',
                    office.status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500',
                  ]">
                    {{ office.status }}
                  </span>
                </div>

                <div v-if="office.histories?.length" class="mt-3 flex flex-wrap gap-1.5">
                  <span
                    v-for="history in office.histories.slice(0, 3)"
                    :key="history.id"
                    class="rounded-full bg-[#F4F8F5] px-2.5 py-1 text-[10px] font-bold text-[#64746B]"
                  >
                    {{ history.effective_from_year }}–{{ history.effective_to_year ?? 'Now' }}
                  </span>
                </div>
              </button>
            </TransitionGroup>

            <div v-else class="rounded-3xl border border-dashed border-[#DDEDE3] bg-[#F8FCF9] p-8 text-center">
              <p class="text-[14px] font-black text-[#064E3B]">No office matched your search</p>
              <p class="mt-1 text-[12px] font-semibold text-[#8FA79B]">Clear the filter before the database starts feeling ignored.</p>
              <button class="mt-4 rounded-xl bg-[#064E3B] px-4 py-2 text-[12px] font-black text-white" @click="clearSearch">Show all offices</button>
            </div>
          </div>
        </section>

        <div class="space-y-5">
          <section class="rounded-[1.5rem] bg-white p-5 shadow-sm ring-1 ring-[#DDEDE3]">
            <div class="mb-5 flex items-center justify-between gap-3">
              <div>
                <h3 class="text-[15px] font-black text-[#064E3B]">
                  {{ formMode === 'edit' ? 'Edit Office' : 'Create Office' }}
                </h3>
                <p class="text-[11px] font-semibold text-[#8FA79B]">Latest name powers main charts. History powers by-year labels.</p>
              </div>
              <button class="rounded-xl px-3 py-1.5 text-[12px] font-black text-[#168A4A] transition-all hover:bg-[#ECFDF3] hover:text-[#064E3B]" @click="openAddOffice">Clear</button>
            </div>

            <div class="space-y-3">
              <div>
                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest text-gray-400">Current / Latest Name</label>
                <input v-model="officeForm.current_name" class="interactive-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] font-semibold outline-none" />
              </div>
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest text-gray-400">Acronym</label>
                  <input v-model="officeForm.acronym" class="interactive-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] font-semibold outline-none" />
                </div>
                <div>
                  <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest text-gray-400">Start Year</label>
                  <input v-model.number="officeForm.effective_from_year" type="number" class="interactive-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] font-semibold outline-none" />
                </div>
                <div>
                  <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest text-gray-400">Status</label>
                  <select v-model="officeForm.status" class="interactive-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] font-semibold outline-none">
                    <option>Active</option>
                    <option>Inactive</option>
                  </select>
                </div>
              </div>
              <button
                :disabled="saving || !officeForm.current_name"
                class="w-full rounded-xl bg-[#064E3B] py-2.5 text-[13px] font-black text-white shadow-lg shadow-[#064E3B]/15 transition-all hover:-translate-y-0.5 hover:bg-[#0F766E] disabled:translate-y-0 disabled:opacity-50"
                @click="saveOffice"
              >
                {{ saving ? 'Saving…' : (formMode === 'edit' ? 'Save Current Name' : 'Create Office') }}
              </button>
            </div>
          </section>

          <section class="rounded-[1.5rem] bg-white p-5 shadow-sm ring-1 ring-[#DDEDE3]">
            <div class="mb-4">
              <h3 class="text-[15px] font-black text-[#064E3B]">Chart Label Preview</h3>
              <p class="text-[11px] font-semibold text-[#8FA79B]">Shows how labels behave in latest vs by-year reports.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
              <div class="rounded-2xl border border-[#DDEDE3] bg-[#F8FCF9] p-4">
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#8FA79B]">Latest chart label</p>
                <p class="mt-2 text-[13px] font-black leading-5 text-[#064E3B]">{{ selectedPreview.latestLabel }}</p>
                <p class="mt-1 text-[11px] font-semibold text-[#8FA79B]">{{ selectedPreview.latestPeriod }}</p>
              </div>
              <div class="rounded-2xl border border-[#DDEDE3] bg-[#F8FCF9] p-4">
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#8FA79B]">By-year label</p>
                <p class="mt-2 text-[13px] font-black leading-5 text-[#064E3B]">{{ selectedPreview.byYearLabel }}</p>
                <p class="mt-1 text-[11px] font-semibold text-[#8FA79B]">{{ selectedPreview.historicalPeriod }}</p>
              </div>
            </div>
            <div class="mt-3 rounded-2xl border border-[#DDEDE3] bg-white p-3">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#8FA79B]">Stable key used for grouping</p>
              <p class="mt-1 font-mono text-[12px] font-black text-[#064E3B]">{{ selectedPreview.identityKey }}</p>
            </div>
          </section>

          <section class="rounded-[1.5rem] bg-white p-5 shadow-sm ring-1 ring-[#DDEDE3]">
            <div v-if="selectedOffice" class="space-y-4">
              <div>
                <h4 class="text-[13px] font-black text-[#064E3B]">Add Previous / Yearly Name</h4>
                <p class="text-[11px] font-semibold text-[#8FA79B]">Example: PRMO from 2023 to 2025, then IPPDD from 2026 onward.</p>
              </div>

              <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="historyForm.name" placeholder="Historical name" class="interactive-input rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] font-semibold outline-none sm:col-span-2" />
                <input v-model="historyForm.acronym" placeholder="Acronym" class="interactive-input rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] font-semibold outline-none" />
                <div class="grid grid-cols-2 gap-2">
                  <input v-model.number="historyForm.effective_from_year" type="number" placeholder="From" class="interactive-input rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] font-semibold outline-none" />
                  <input v-model.number="historyForm.effective_to_year" type="number" placeholder="To" class="interactive-input rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] font-semibold outline-none" />
                </div>
              </div>

              <button
                :disabled="saving || !historyForm.name"
                class="w-full rounded-xl border border-[#DDEDE3] bg-[#F8FCF9] py-2.5 text-[13px] font-black text-[#064E3B] transition-all hover:-translate-y-0.5 hover:bg-[#ECFDF3] disabled:translate-y-0 disabled:opacity-50"
                @click="saveHistory"
              >
                Add Historical Name
              </button>

              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <h4 class="text-[13px] font-black text-[#064E3B]">Name Timeline</h4>
                  <span class="rounded-full bg-[#ECFDF3] px-3 py-1 text-[10px] font-black text-[#064E3B]">{{ selectedTimeline.length }} records</span>
                </div>
                <TransitionGroup v-if="selectedTimeline.length" name="list" tag="div" class="relative space-y-3 before:absolute before:bottom-3 before:left-[1.05rem] before:top-3 before:w-px before:bg-[#DDEDE3]">
                  <div v-for="history in selectedTimeline" :key="history.id" class="relative flex gap-3 rounded-2xl border border-[#EEF5F0] bg-[#F8FCF9] p-3">
                    <div :class="['relative z-10 mt-0.5 h-8 w-8 shrink-0 rounded-full border-4 border-white shadow-sm', history.isCurrent ? 'bg-[#168A4A]' : 'bg-[#B7F4CE]']" />
                    <div class="min-w-0 flex-1">
                      <div class="flex items-start justify-between gap-3">
                        <div>
                          <p class="text-[12px] font-black text-[#064E3B]">{{ history.name }}</p>
                          <p class="mt-1 text-[11px] font-semibold text-[#8FA79B]">
                            {{ history.period }}
                            <span v-if="history.acronym"> · {{ history.acronym }}</span>
                          </p>
                        </div>
                        <button class="rounded-lg px-2 py-1 text-[11px] font-black text-red-500 hover:bg-red-50 hover:text-red-700" @click="removeHistory(history.original)">Remove</button>
                      </div>
                    </div>
                  </div>
                </TransitionGroup>
                <div v-else class="rounded-2xl border border-dashed border-[#DDEDE3] bg-[#F8FCF9] p-5 text-center text-[13px] font-semibold text-[#8FA79B]">
                  No yearly name yet. Add one so historical reports have something smarter to say.
                </div>
              </div>
            </div>

            <div v-else class="rounded-2xl border border-dashed border-[#DDEDE3] p-5 text-center text-[13px] font-semibold text-[#8FA79B]">
              Select an office to manage its name history.
            </div>
          </section>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.registry-hero { animation: heroRise .45s ease-out both; }
.registry-grid {
  background-image: linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px);
  background-size: 34px 34px;
  mask-image: linear-gradient(to bottom, black, transparent 85%);
}

.stat-card { transition: transform .2s ease, box-shadow .2s ease; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(6, 78, 59, .08); }

.interactive-input { transition: box-shadow .18s ease, border-color .18s ease, transform .18s ease; }
.interactive-input:focus { border-color: rgba(22, 138, 74, .35); box-shadow: 0 0 0 4px rgba(83, 210, 140, .15); transform: translateY(-1px); }

.toast-enter-active,
.toast-leave-active { transition: opacity .25s ease, transform .25s ease; }
.toast-enter-from,
.toast-leave-to { opacity: 0; transform: translateY(-8px); }

.list-enter-active,
.list-leave-active { transition: all .22s ease; }
.list-enter-from,
.list-leave-to { opacity: 0; transform: translateY(8px) scale(.98); }
.list-move { transition: transform .22s ease; }

@keyframes heroRise {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
