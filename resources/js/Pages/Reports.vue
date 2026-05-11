<script setup>
import { computed, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/PageHero.vue'
import SectionCard from '@/Components/SectionCard.vue'
import MiniMetric from '@/Components/MiniMetric.vue'

const reports = [
  {
    title: 'Budget Summary Report',
    desc: 'Total budget per office and fund source for the selected fiscal year.',
    icon: 'chart',
    format: 'PDF',
    status: 'Ready for template',
    audience: 'President / VP / Supervisor',
  },
  {
    title: 'Performance Indicators Report',
    desc: 'List of performance indicators, targets, timelines, and success indicators.',
    icon: 'target',
    format: 'PDF',
    status: 'Template required',
    audience: 'Quality assurance review',
  },
  {
    title: 'Year-Over-Year Comparison',
    desc: 'Budget movement across fiscal years with increase and decrease indicators.',
    icon: 'trend',
    format: 'XLSX',
    status: 'Export ready',
    audience: 'Budget analysis',
  },
  {
    title: 'Full WFP Data Export',
    desc: 'Complete raw WFP dataset for backup, validation, and external checking.',
    icon: 'table',
    format: 'XLSX',
    status: 'Export ready',
    audience: 'Technical validation',
  },
]

const selectedYear = ref(2026)
const formatFilter = ref('All')
const search = ref('')
const generating = ref(null)

const filteredReports = computed(() => reports.filter((report) => {
  const matchesFormat = formatFilter.value === 'All' || report.format === formatFilter.value
  const searchText = `${report.title} ${report.desc} ${report.audience}`.toLowerCase()
  return matchesFormat && searchText.includes(search.value.toLowerCase())
}))

const exportReadyCount = computed(() => reports.filter((report) => report.status === 'Export ready').length)
const pdfCount = computed(() => reports.filter((report) => report.format === 'PDF').length)
const excelCount = computed(() => reports.filter((report) => report.format === 'XLSX').length)

const generate = (index) => {
  generating.value = index
  window.setTimeout(() => {
    generating.value = null
  }, 900)
}
</script>

<template>
  <AppLayout>
    <template #breadcrumb>
      <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#168A4A]">Reports Center</p>
    </template>
    <template #title>Reports & Export</template>
    <template #subtitle>Generate polished summaries and spreadsheet exports for review</template>

    <div class="space-y-5">
      <PageHero
        eyebrow="Export Workspace"
        title="Defense-ready reports, cleaner exports, fewer spreadsheet nightmares"
        subtitle="Use this area to prepare annual budget summaries, full WFP exports, and year-over-year comparison files. The cards are arranged by purpose so your supervisor does not need to hunt through buttons like it is a treasure map."
        tone="gold"
      >
        <template #stats>
          <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Selected Year</p>
              <p class="mt-1 text-2xl font-black text-white">FY {{ selectedYear }}</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Export-ready</p>
              <p class="mt-1 text-2xl font-black text-white">{{ exportReadyCount }} reports</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Formats</p>
              <p class="mt-1 text-2xl font-black text-white">PDF + XLSX</p>
            </div>
          </div>
        </template>
      </PageHero>

      <div class="grid gap-4 md:grid-cols-3">
        <MiniMetric label="PDF Templates" :value="pdfCount" note="Printable report format" />
        <MiniMetric label="Excel Exports" :value="excelCount" note="Editable validation files" />
        <MiniMetric label="Supervisor View" value="Grouped" note="By purpose and audience" />
      </div>

      <SectionCard title="Report Controls" subtitle="Filter the cards before generating or exporting">
        <div class="grid gap-3 lg:grid-cols-[1fr_auto_auto] lg:items-center">
          <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#8FA79B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" />
            </svg>
            <input
              v-model="search"
              type="search"
              placeholder="Search report name, use, or audience..."
              class="w-full rounded-2xl border border-[#DDEDE3] bg-[#F8FCF9] py-3 pl-10 pr-4 text-sm font-semibold text-[#064E3B] placeholder:text-[#8FA79B]"
            />
          </div>

          <select v-model="selectedYear" class="rounded-2xl border border-[#DDEDE3] bg-white px-4 py-3 text-sm font-black text-[#064E3B]">
            <option :value="2024">FY 2024</option>
            <option :value="2025">FY 2025</option>
            <option :value="2026">FY 2026</option>
          </select>

          <div class="inline-flex rounded-2xl border border-[#DDEDE3] bg-[#F8FCF9] p-1">
            <button
              v-for="format in ['All', 'PDF', 'XLSX']"
              :key="format"
              @click="formatFilter = format"
              :class="[
                'rounded-xl px-4 py-2 text-[12px] font-black transition-all',
                formatFilter === format ? 'bg-[#064E3B] text-white shadow-md shadow-[#064E3B]/15' : 'text-[#64746B] hover:bg-white hover:text-[#064E3B]',
              ]"
            >
              {{ format }}
            </button>
          </div>
        </div>
      </SectionCard>

      <div class="grid gap-4 lg:grid-cols-2">
        <article
          v-for="(report, index) in filteredReports"
          :key="report.title"
          class="interactive-card rounded-[1.35rem] border border-[#DDEDE3] bg-white p-5 shadow-[0_14px_40px_rgba(6,78,59,0.08)]"
        >
          <div class="flex items-start gap-4">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#ECFDF3] text-[#168A4A] ring-1 ring-[#DDEDE3]">
              <svg v-if="report.icon === 'chart'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 18V10M8 18V6M13 18v-4M18 18V8"/><line x1="3" y1="18" x2="21" y2="18"/>
              </svg>
              <svg v-if="report.icon === 'target'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1" fill="currentColor"/>
              </svg>
              <svg v-if="report.icon === 'trend'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
              </svg>
              <svg v-if="report.icon === 'table'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/>
              </svg>
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-[15px] font-black text-[#064E3B]">{{ report.title }}</h3>
                <span :class="[
                  'rounded-full px-2.5 py-1 text-[10px] font-black ring-1',
                  report.format === 'PDF' ? 'bg-red-50 text-red-500 ring-red-100' : 'bg-emerald-50 text-emerald-600 ring-emerald-100',
                ]">
                  {{ report.format }}
                </span>
              </div>
              <p class="mt-2 text-[12px] font-medium leading-5 text-[#64746B]">{{ report.desc }}</p>

              <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <div class="rounded-2xl bg-[#F8FCF9] p-3 ring-1 ring-[#E6F2EA]">
                  <p class="text-[9px] font-black uppercase tracking-[0.14em] text-[#8FA79B]">Audience</p>
                  <p class="mt-1 text-[12px] font-black text-[#064E3B]">{{ report.audience }}</p>
                </div>
                <div class="rounded-2xl bg-[#F8FCF9] p-3 ring-1 ring-[#E6F2EA]">
                  <p class="text-[9px] font-black uppercase tracking-[0.14em] text-[#8FA79B]">Status</p>
                  <p class="mt-1 text-[12px] font-black text-[#168A4A]">{{ report.status }}</p>
                </div>
              </div>

              <button
                @click="generate(index)"
                :disabled="generating === index"
                class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-[#064E3B] px-4 py-2.5 text-[12px] font-black text-white shadow-lg shadow-[#064E3B]/15 hover:-translate-y-0.5 hover:bg-[#168A4A] disabled:opacity-60"
              >
                <svg v-if="generating === index" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ generating === index ? 'Preparing preview…' : `Prepare ${report.format} · FY ${selectedYear}` }}
              </button>
            </div>
          </div>
        </article>
      </div>

      <SectionCard title="Export Readiness Notes" subtitle="Keeps the panel discussion honest, which is unfair but useful">
        <div class="grid gap-3 md:grid-cols-3">
          <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
            <p class="text-[12px] font-black text-amber-800">PDF package</p>
            <p class="mt-1 text-[11px] font-semibold leading-5 text-amber-700">Install <code class="rounded bg-amber-100 px-1">barryvdh/laravel-dompdf</code> when PDF generation is activated.</p>
          </div>
          <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
            <p class="text-[12px] font-black text-emerald-800">Excel package</p>
            <p class="mt-1 text-[11px] font-semibold leading-5 text-emerald-700">Use <code class="rounded bg-emerald-100 px-1">maatwebsite/excel</code> for formal spreadsheet exports.</p>
          </div>
          <div class="rounded-2xl border border-[#DDEDE3] bg-[#F8FCF9] p-4">
            <p class="text-[12px] font-black text-[#064E3B]">Office naming</p>
            <p class="mt-1 text-[11px] font-semibold leading-5 text-[#64746B]">Reports can group by stable office identity while still showing historical names by year.</p>
          </div>
        </div>
      </SectionCard>
    </div>
  </AppLayout>
</template>
