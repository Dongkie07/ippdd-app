<script setup>
import { ref, computed } from 'vue'
import AppLayout   from '@/Layouts/AppLayout.vue'
import SectionCard from '@/Components/SectionCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import axios       from 'axios'

const props = defineProps({ history: { type: Array, default: () => [] } })

// ── State ─────────────────────────────────────────────────────
const stage    = ref('idle')   // idle | uploading | saving | done
const file     = ref(null)
const year     = ref(2026)
const dragging = ref(false)
const error    = ref(null)

// Modal
const showModal    = ref(false)
const previewRows  = ref([])
const filename     = ref('')
const parseStats   = ref({})
const savedMessage = ref('')

// ── Computed ──────────────────────────────────────────────────
const selectedRows = computed(() => previewRows.value.filter(r => r.selected))
const totalBudget  = computed(() => selectedRows.value.reduce((s, r) => s + parseFloat(r.budget || 0), 0))
const totalPIs     = computed(() => selectedRows.value.reduce((s, r) => s + parseInt(r.pi_count || 0), 0))

// ── File pick ─────────────────────────────────────────────────
const onPick = (e) => {
    dragging.value = false
    const f = e.dataTransfer?.files?.[0] ?? e.target?.files?.[0]
    if (!f) return
    if (!f.name.match(/\.(xlsx|xls)$/i)) {
        error.value = 'Please select an Excel file (.xlsx or .xls only)'
        return
    }
    file.value  = f
    error.value = null
}

// ── Step 1: Upload & Parse → open modal ──────────────────────
const uploadAndParse = async () => {
    if (!file.value) return
    stage.value = 'uploading'
    error.value = null

    const form = new FormData()
    form.append('file', file.value)
    form.append('year', year.value)
    form.append('_token', document.querySelector('meta[name=csrf-token]')?.content ?? '')

    try {
        const res = await axios.post('/upload/parse', form, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })

        if (res.data.success) {
            previewRows.value = res.data.preview.map(r => ({
                ...r,
                selected: true,
                _editing: false,
            }))
            filename.value  = res.data.filename
            parseStats.value = {
                total_budget: res.data.total_budget,
                total_depts:  res.data.total_depts,
                year:         res.data.year,
            }
            stage.value    = 'idle'
            showModal.value = true
        } else {
            error.value = res.data.error ?? 'Parsing failed.'
            stage.value = 'idle'
        }
    } catch (err) {
        error.value = err.response?.data?.message
            ?? err.response?.data?.error
            ?? 'Upload failed — check the file and try again.'
        stage.value = 'idle'
    }
}

// ── Edit helpers ──────────────────────────────────────────────
const toggleRow  = (i) => previewRows.value[i].selected = !previewRows.value[i].selected
const toggleEdit = (i) => previewRows.value[i]._editing  = !previewRows.value[i]._editing
const selectAll  = (v) => previewRows.value.forEach(r => r.selected = v)

// ── Step 2: Confirm & Save ────────────────────────────────────
const confirmImport = async () => {
    if (!selectedRows.value.length) {
        error.value = 'Please select at least one department.'
        return
    }
    stage.value = 'saving'
    error.value = null

    try {
        const res = await axios.post('/upload/confirm', {
            rows:     selectedRows.value.map(({ _editing, sample_pis, ...r }) => r),
            filename: filename.value,
            _token:   document.querySelector('meta[name=csrf-token]')?.content ?? '',
        })

        if (res.data.success) {
            savedMessage.value = res.data.message
            showModal.value    = false
            stage.value        = 'done'
        } else {
            error.value = res.data.error
            stage.value = 'idle'
        }
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Save failed.'
        stage.value = 'idle'
    }
}

const reset = () => {
    stage.value     = 'idle'
    file.value      = null
    error.value     = null
    showModal.value = false
    previewRows.value = []
}

// ── Format helpers ────────────────────────────────────────────
const php  = v => '₱' + parseFloat(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })
const phpM = v => '₱' + (parseFloat(v || 0) / 1e6).toFixed(2) + 'M'
</script>

<template>
  <AppLayout>
    <template #breadcrumb>Upload WFP Data</template>
    <template #title>Upload WFP Data</template>
    <template #subtitle>Import Work & Financial Plan Excel files · Preview before saving</template>

    <div class="space-y-5 max-w-2xl">

      <!-- ── SUCCESS BANNER ────────────────────────────────────── -->
      <div v-if="stage === 'done'"
        class="flex items-center gap-4 bg-emerald-50 border border-emerald-200 rounded-2xl px-5 py-4">
        <div class="w-10 h-10 rounded-full bg-emerald-100 border-2 border-emerald-300 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <div class="flex-1">
          <p class="font-bold text-emerald-800 text-[13px]">Import Successful!</p>
          <p class="text-[12px] text-emerald-600 mt-0.5">{{ savedMessage }}</p>
        </div>
        <div class="flex gap-2">
          <a href="/" class="text-[12px] font-bold text-emerald-700 bg-emerald-100 hover:bg-emerald-200 transition-colors px-3 py-1.5 rounded-lg">
            View Dashboard →
          </a>
          <button @click="reset" class="text-[12px] font-bold text-emerald-600 hover:text-emerald-800 px-3 py-1.5 rounded-lg transition-colors">
            Upload Another
          </button>
        </div>
      </div>

      <!-- ── ERROR BANNER ───────────────────────────────────────── -->
      <div v-if="error"
        class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
        <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p class="text-xs text-red-700 font-semibold">{{ error }}</p>
      </div>

      <!-- ── UPLOAD CARD ────────────────────────────────────────── -->
      <SectionCard title="Select WFP Excel File"
        subtitle="Upload the official DNSC Work & Financial Plan file · parsed data will appear for review before saving">

        <!-- Year tabs -->
        <div class="flex items-center gap-3 mb-5">
          <label class="text-[11px] font-extrabold uppercase tracking-widest text-gray-400 shrink-0">Fiscal Year</label>
          <div class="flex gap-1.5">
            <button v-for="y in [2024,2025,2026,2027]" :key="y" @click="year = y"
              :class="['px-3.5 py-1.5 rounded-xl text-[13px] font-bold border transition-all',
                year === y
                  ? 'bg-[#0D2137] text-white border-[#0D2137] shadow-sm'
                  : 'bg-white text-gray-400 border-gray-200 hover:border-[#0D2137]/40']">
              {{ y }}
            </button>
          </div>
        </div>

        <!-- Drop zone -->
        <div
          @dragover.prevent="dragging = true"
          @dragleave="dragging = false"
          @drop.prevent="onPick"
          @click="$refs.fi.click()"
          :class="[
            'border-2 border-dashed rounded-2xl py-14 text-center cursor-pointer transition-all select-none',
            file    ? 'border-[#0D2137]/30 bg-[#0D2137]/[0.025]'
            : dragging ? 'border-[#C9A84C] bg-[#C9A84C]/5 scale-[1.01]'
            : 'border-gray-200 hover:border-[#0D2137]/40 hover:bg-gray-50/80'
          ]">
          <input ref="fi" type="file" accept=".xlsx,.xls" class="hidden" @change="onPick" />

          <!-- Empty state -->
          <template v-if="!file">
            <div class="w-14 h-14 rounded-2xl bg-[#0D2137]/6 border border-[#0D2137]/10 flex items-center justify-center mx-auto mb-4">
              <svg class="w-7 h-7 text-[#0D2137]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4-4 4M12 4v12"/>
              </svg>
            </div>
            <p class="font-bold text-gray-600">Drop your WFP Excel file here</p>
            <p class="text-[12px] text-gray-400 mt-1">or click to browse &nbsp;·&nbsp; .xlsx or .xls only</p>
          </template>

          <!-- File selected -->
          <template v-else>
            <div class="flex items-center justify-center gap-4 px-8">
              <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                </svg>
              </div>
              <div class="text-left">
                <p class="font-bold text-[#0D2137] text-[13px]">{{ file.name }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ (file.size/1024).toFixed(0) }} KB &nbsp;·&nbsp; FY {{ year }}</p>
              </div>
              <button @click.stop="file = null; error = null"
                class="ml-2 text-gray-300 hover:text-red-400 transition-colors p-1.5 rounded-lg hover:bg-red-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
          </template>
        </div>

        <!-- Upload button -->
        <button v-if="file"
          @click="uploadAndParse"
          :disabled="stage === 'uploading'"
          class="mt-4 w-full py-3 rounded-xl bg-[#0D2137] text-white font-bold text-[13px] tracking-wide
                 hover:bg-[#1A5276] transition-colors disabled:opacity-60
                 flex items-center justify-center gap-2.5 shadow-sm">
          <svg v-if="stage === 'uploading'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
          </svg>
          {{ stage === 'uploading' ? 'Reading Excel file…' : `Parse & Preview FY ${year} Data` }}
        </button>

        <!-- How it works -->
        <div class="mt-5 grid grid-cols-3 gap-3">
          <div v-for="(step, i) in [
            { n:'1', label:'Upload', desc:'Select your WFP Excel file' },
            { n:'2', label:'Preview', desc:'Review & edit parsed data' },
            { n:'3', label:'Save',   desc:'Confirm to update database' },
          ]" :key="i"
            class="flex items-start gap-2.5 p-3 rounded-xl bg-gray-50 border border-gray-100">
            <span class="w-5 h-5 rounded-full bg-[#0D2137] text-white text-[10px] font-extrabold flex items-center justify-center shrink-0 mt-0.5">{{ step.n }}</span>
            <div>
              <p class="text-[11px] font-bold text-[#0D2137]">{{ step.label }}</p>
              <p class="text-[10px] text-gray-400 mt-0.5">{{ step.desc }}</p>
            </div>
          </div>
        </div>
      </SectionCard>

      <!-- ── IMPORT HISTORY ──────────────────────────────────────── -->
      <SectionCard v-if="history?.length" title="Import History" :noPad="true">
        <table class="w-full text-[13px]">
          <thead>
            <tr class="border-b-2 border-gray-100">
              <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">File</th>
              <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Year</th>
              <th class="text-right px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Depts</th>
              <th class="text-right px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Total Budget</th>
              <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Date</th>
              <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="h in history" :key="h.id" class="border-b border-gray-50 hover:bg-[#0D2137]/[0.02]">
              <td class="px-6 py-3.5 font-mono text-[11px] text-gray-500 max-w-[180px] truncate">{{ h.filename }}</td>
              <td class="px-6 py-3.5 font-bold text-[#0D2137]">FY {{ h.year }}</td>
              <td class="px-6 py-3.5 text-right text-gray-500">{{ h.dept_count }}</td>
              <td class="px-6 py-3.5 text-right font-mono font-bold text-[#0D2137] text-[12px]">{{ phpM(h.total_budget) }}</td>
              <td class="px-6 py-3.5 text-[11px] text-gray-400">
                {{ new Date(h.created_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'}) }}
              </td>
              <td class="px-6 py-3.5"><StatusBadge status="completed" label="Imported" /></td>
            </tr>
          </tbody>
        </table>
      </SectionCard>
    </div>

    <!-- ════════════════════════════════════════════════════════
         PREVIEW MODAL
    ════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
          style="font-family: 'Plus Jakarta Sans', sans-serif;">

          <!-- Backdrop -->
          <div class="absolute inset-0 bg-[#0D2137]/60 backdrop-blur-sm" @click="showModal = false" />

          <!-- Modal panel -->
          <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden">

            <!-- Modal header -->
            <div class="flex items-center justify-between px-7 py-5 border-b border-gray-100 shrink-0">
              <div>
                <h2 class="text-[16px] font-extrabold text-[#0D2137] tracking-tight">Preview Parsed Data</h2>
                <p class="text-[12px] text-gray-400 mt-0.5 font-medium">
                  Review and edit before saving to database &nbsp;·&nbsp; {{ filename }}
                </p>
              </div>
              <button @click="showModal = false" class="w-8 h-8 rounded-xl text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>

            <!-- Stats strip -->
            <div class="grid grid-cols-4 divide-x divide-gray-100 border-b border-gray-100 shrink-0">
              <div class="px-6 py-3.5 text-center">
                <p class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-gray-400">Fiscal Year</p>
                <p class="text-[18px] font-extrabold text-[#0D2137] mt-0.5">FY {{ parseStats.year }}</p>
              </div>
              <div class="px-6 py-3.5 text-center">
                <p class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-gray-400">Detected</p>
                <p class="text-[18px] font-extrabold text-[#0D2137] mt-0.5">{{ previewRows.length }} <span class="text-xs text-gray-400 font-medium">depts</span></p>
              </div>
              <div class="px-6 py-3.5 text-center">
                <p class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-gray-400">Selected</p>
                <p class="text-[18px] font-extrabold text-[#0D2137] mt-0.5">{{ selectedRows.length }} <span class="text-xs text-gray-400 font-medium">/ {{ previewRows.length }}</span></p>
              </div>
              <div class="px-6 py-3.5 text-center">
                <p class="text-[9px] font-extrabold uppercase tracking-[0.15em] text-gray-400">Total Budget</p>
                <p class="text-[18px] font-extrabold text-[#0D2137] mt-0.5">{{ phpM(totalBudget) }}</p>
              </div>
            </div>

            <!-- Instruction banner -->
            <div class="flex items-center gap-3 bg-amber-50 border-b border-amber-100 px-7 py-2.5 shrink-0">
              <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
              <p class="text-[11px] text-amber-700 font-semibold">
                Click <strong>Edit</strong> on any row to correct department name, budget, or PI count.
                Uncheck rows to exclude them. Click <strong>Confirm & Save</strong> when ready.
              </p>
            </div>

            <!-- Table (scrollable) -->
            <div class="overflow-y-auto flex-1">
              <table class="w-full text-[13px]">
                <thead class="sticky top-0 bg-white z-10">
                  <tr class="border-b-2 border-gray-100">
                    <th class="px-5 py-3 w-10">
                      <!-- Select all checkbox -->
                      <button @click="selectAll(selectedRows.length < previewRows.length)"
                        :class="['w-4 h-4 rounded border-2 flex items-center justify-center transition-all mx-auto',
                          selectedRows.length === previewRows.length
                            ? 'bg-[#0D2137] border-[#0D2137]'
                            : selectedRows.length > 0
                            ? 'bg-[#0D2137]/30 border-[#0D2137]/40'
                            : 'border-gray-300']">
                        <svg v-if="selectedRows.length > 0" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                          <polyline points="20 6 9 17 4 12"/>
                        </svg>
                      </button>
                    </th>
                    <th class="text-left px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-8">#</th>
                    <th class="text-left px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department Name</th>
                    <th class="text-left px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-24">Code</th>
                    <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-40">Budget (₱)</th>
                    <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-20">PIs</th>
                    <th class="px-4 py-3 w-20"></th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="(row, i) in previewRows" :key="i">

                    <!-- Data row -->
                    <tr :class="[
                      'border-b border-gray-50 transition-colors',
                      row.selected
                        ? 'hover:bg-blue-50/30'
                        : 'opacity-40 bg-gray-50/80'
                    ]">

                      <!-- Checkbox -->
                      <td class="px-5 py-3.5 text-center">
                        <button @click="toggleRow(i)"
                          :class="['w-4 h-4 rounded border-2 flex items-center justify-center transition-all mx-auto',
                            row.selected ? 'bg-[#0D2137] border-[#0D2137]' : 'border-gray-300 bg-white']">
                          <svg v-if="row.selected" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12"/>
                          </svg>
                        </button>
                      </td>

                      <!-- Row number -->
                      <td class="px-4 py-3.5 text-gray-300 font-mono text-[11px] font-bold">{{ String(i+1).padStart(2,'0') }}</td>

                      <!-- Department (editable) -->
                      <td class="px-4 py-3.5">
                        <input v-if="row._editing"
                          v-model="row.department"
                          @keyup.enter="toggleEdit(i)"
                          class="w-full text-[13px] font-semibold text-[#0D2137] border-0 border-b-2 border-[#C9A84C] outline-none bg-transparent pb-0.5 focus:ring-0"
                        />
                        <span v-else class="font-semibold text-[#0D2137]">{{ row.department }}</span>
                      </td>

                      <!-- Code badge -->
                      <td class="px-4 py-3.5">
                        <span class="bg-[#0D2137]/6 text-[#0D2137] text-[10px] font-mono font-bold px-2 py-0.5 rounded-lg border border-[#0D2137]/10 whitespace-nowrap">
                          {{ row.sheet_code }}
                        </span>
                      </td>

                      <!-- Budget (editable) -->
                      <td class="px-4 py-3.5 text-right">
                        <input v-if="row._editing"
                          v-model.number="row.budget"
                          type="number"
                          @keyup.enter="toggleEdit(i)"
                          class="w-36 text-right text-[13px] font-mono font-bold text-[#0D2137] border-0 border-b-2 border-[#C9A84C] outline-none bg-transparent pb-0.5"
                        />
                        <span v-else class="font-mono font-bold text-[#0D2137] text-[13px]">{{ php(row.budget) }}</span>
                      </td>

                      <!-- PI count (editable) -->
                      <td class="px-4 py-3.5 text-right">
                        <input v-if="row._editing"
                          v-model.number="row.pi_count"
                          type="number"
                          @keyup.enter="toggleEdit(i)"
                          class="w-14 text-center text-[13px] font-bold text-[#0D2137] border-0 border-b-2 border-[#C9A84C] outline-none bg-transparent pb-0.5"
                        />
                        <span v-else
                          class="inline-flex items-center justify-center bg-[#C9A84C]/10 text-[#8B6914] text-[11px] font-bold px-2.5 py-0.5 rounded-full border border-[#C9A84C]/20 min-w-[32px]">
                          {{ row.pi_count }}
                        </span>
                      </td>

                      <!-- Edit button -->
                      <td class="px-4 py-3.5 text-center">
                        <button @click="toggleEdit(i)"
                          :class="['text-[11px] font-bold px-2.5 py-1 rounded-lg transition-all',
                            row._editing
                              ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'
                              : 'text-gray-400 hover:text-[#0D2137] hover:bg-gray-100']">
                          {{ row._editing ? '✓ Done' : 'Edit' }}
                        </button>
                      </td>
                    </tr>

                    <!-- Sample PIs (shown while editing) -->
                    <tr v-if="row._editing && row.sample_pis?.length"
                      class="bg-amber-50/50 border-b border-amber-100">
                      <td colspan="7" class="px-10 py-3">
                        <p class="text-[10px] font-extrabold uppercase tracking-widest text-amber-600 mb-2">
                          Sample Performance Indicators parsed from this sheet:
                        </p>
                        <div v-for="pi in row.sample_pis" :key="pi.row"
                          class="flex items-center gap-4 text-[11px] text-gray-600 py-1 border-b border-amber-100/60 last:border-0">
                          <span class="text-gray-300 font-mono shrink-0">Row {{ pi.row }}</span>
                          <span class="flex-1 truncate">{{ pi.pi || '—' }}</span>
                          <span class="font-mono font-bold text-[#0D2137] shrink-0">{{ php(pi.budget) }}</span>
                        </div>
                        <p class="text-[10px] text-amber-500 font-medium mt-2">
                          ℹ Budget total above = sum of all {{ row.pi_count }} budget rows in this sheet
                        </p>
                      </td>
                    </tr>

                  </template>
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
                    <td class="px-4 py-3.5 text-right font-extrabold text-[#0D2137]">
                      {{ totalPIs }}
                    </td>
                    <td />
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Modal footer / actions -->
            <div class="flex items-center justify-between gap-4 px-7 py-4 border-t border-gray-100 bg-gray-50/50 shrink-0">
              <div class="flex items-center gap-2">
                <button @click="selectAll(true)"  class="text-[11px] font-bold text-[#0D2137] hover:text-[#C9A84C] transition-colors">Select All</button>
                <span class="text-gray-300 text-xs">|</span>
                <button @click="selectAll(false)" class="text-[11px] font-bold text-gray-400 hover:text-red-500 transition-colors">Deselect All</button>
              </div>

              <div class="flex items-center gap-3">
                <button @click="showModal = false"
                  class="px-5 py-2.5 rounded-xl border border-gray-200 text-[13px] font-semibold text-gray-500 hover:border-gray-300 hover:text-gray-700 transition-all">
                  Cancel
                </button>
                <button
                  @click="confirmImport"
                  :disabled="stage === 'saving' || !selectedRows.length"
                  class="px-6 py-2.5 rounded-xl bg-[#0D2137] text-white font-bold text-[13px] tracking-wide
                         hover:bg-[#1A5276] transition-colors disabled:opacity-50
                         flex items-center gap-2 shadow-sm">
                  <svg v-if="stage === 'saving'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                  {{ stage === 'saving'
                    ? 'Saving…'
                    : `Confirm & Save ${selectedRows.length} Dept${selectedRows.length !== 1 ? 's' : ''} to Database` }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </AppLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .relative, .modal-leave-active .relative { transition: transform 0.25s ease; }
.modal-enter-from .relative { transform: scale(0.96) translateY(8px); }
.modal-leave-to .relative { transform: scale(0.96) translateY(8px); }
</style>