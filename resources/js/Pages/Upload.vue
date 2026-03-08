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
const totalBudget  = computed(() => selectedRows.value.reduce((s, r) => s + parseFloat(r.budget_total ?? r.budget ?? 0), 0))


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

// ── Add Office modal ───────────────────────────────────────────
const showAddOffice = ref(false)
const newOffice     = ref({ department: '', sheet_code: '', budget_total: 0, parent_dept: '', is_parent: false })
const parentOptions = computed(() => previewRows.value.filter(r => !r.parent_dept))

const openAddOffice = (parentDept = '') => {
  newOffice.value = { department: '', sheet_code: '', budget_total: 0, parent_dept: parentDept, is_parent: false }
  showAddOffice.value = true
}
const saveNewOffice = () => {
  if (!newOffice.value.department.trim()) return
  const budget = parseFloat(newOffice.value.budget_total) || 0
  previewRows.value.push({
    no: '', department: newOffice.value.department.trim(),
    sheet_code:   newOffice.value.sheet_code.trim() || newOffice.value.department.slice(0,6).toUpperCase(),
    budget: budget, budget_total: budget,
    parent_dept: newOffice.value.parent_dept || null,
    is_parent: newOffice.value.is_parent,
    selected: true, _editing: false,
    fund_101:0, fund_164:0, fund_161:0, fund_163:0,
    budget_fund_101:0, budget_fund_164:0, budget_fund_161:0, budget_fund_163:0,
  })
  showAddOffice.value = false
}

// ── Move-to-parent popup ───────────────────────────────────────
const moveTarget    = ref(null)   // the row being moved
const moveTargetIdx = ref(-1)
const showMovePopup = ref(false)

const openMovePopup = (row, idx) => {
  moveTarget.value    = row
  moveTargetIdx.value = idx
  showMovePopup.value = true
}
const applyMove = (newParent) => {
  const idx = previewRows.value.findIndex(r => r.department === moveTarget.value.department && r.sheet_code === moveTarget.value.sheet_code)
  if (idx !== -1) {
    previewRows.value[idx].parent_dept = newParent || null
    previewRows.value[idx].is_parent   = false
    // If assigning to a parent, mark that parent as is_parent
    if (newParent) {
      const pIdx = previewRows.value.findIndex(r => r.department === newParent)
      if (pIdx !== -1) previewRows.value[pIdx].is_parent = true
    }
  }
  showMovePopup.value = false
}
const removeFromParent = (row) => {
  const idx = previewRows.value.findIndex(r => r.department === row.department && r.sheet_code === row.sheet_code)
  if (idx !== -1) previewRows.value[idx].parent_dept = null
}

// ── Drag-and-drop ──────────────────────────────────────────────
const dragRow      = ref(null)   // row being dragged
const dragOver     = ref(null)   // row being hovered over
const dragOverPos  = ref(null)   // 'above' | 'below' | 'into'

const onDragStart = (e, row) => {
  dragRow.value = row
  e.dataTransfer.effectAllowed = 'move'
}
const onDragOver = (e, row) => {
  e.preventDefault()
  if (!dragRow.value || dragRow.value.department === row.department) return
  dragOver.value = row.department
  // Detect if hovering center (into) or top/bottom
  const rect = e.currentTarget.getBoundingClientRect()
  const y    = e.clientY - rect.top
  const pct  = y / rect.height
  if (!row._isChild && pct > 0.25 && pct < 0.75) {
    dragOverPos.value = 'into'   // drop INTO this parent
  } else {
    dragOverPos.value = pct < 0.5 ? 'above' : 'below'
  }
}
const onDragLeave = () => { dragOver.value = null; dragOverPos.value = null }
const onDrop = (e, targetRow) => {
  e.preventDefault()
  if (!dragRow.value || dragRow.value.department === targetRow.department) {
    dragRow.value = null; dragOver.value = null; return
  }

  const srcIdx = previewRows.value.findIndex(r => r.department === dragRow.value.department && r.sheet_code === dragRow.value.sheet_code)
  if (srcIdx === -1) { dragRow.value = null; dragOver.value = null; return }

  if (dragOverPos.value === 'into' && !targetRow._isChild) {
    // Drop INTO target → make dragged row a child of target
    previewRows.value[srcIdx].parent_dept = targetRow.department
    previewRows.value[srcIdx].is_parent   = false
    const pIdx = previewRows.value.findIndex(r => r.department === targetRow.department)
    if (pIdx !== -1) previewRows.value[pIdx].is_parent = true
  } else {
    // Reorder: move dragged row before/after target
    const [moved] = previewRows.value.splice(srcIdx, 1)
    const newIdx  = previewRows.value.findIndex(r => r.department === targetRow.department && r.sheet_code === targetRow.sheet_code)
    const insertAt = dragOverPos.value === 'above' ? newIdx : newIdx + 1
    previewRows.value.splice(insertAt, 0, moved)
  }

  dragRow.value = null; dragOver.value = null; dragOverPos.value = null
}
const onDragEnd = () => { dragRow.value = null; dragOver.value = null }

// ── Group rows: parents first with children nested ─────────────
const groupedRows = computed(() => {
  const parents = previewRows.value.filter(r => !r.parent_dept)
  const result  = []
  parents.forEach(p => {
    result.push({ ...p, _isChild: false })
    previewRows.value.filter(r => r.parent_dept === p.department)
      .forEach(c => result.push({ ...c, _isChild: true }))
  })
  // Orphans (parent_dept set but parent not found)
  const inResult = new Set(result.map(r => r.department + r.sheet_code))
  previewRows.value.forEach(r => {
    if (!inResult.has(r.department + r.sheet_code)) result.push({ ...r, _isChild: !!r.parent_dept })
  })
  return result
})

const previewIndex = (row) => previewRows.value.findIndex(r => r.department === row.department && r.sheet_code === row.sheet_code)

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
            rows: selectedRows.value.map(({ _editing, sample_pis, pi_count, budget, ...r }) => ({
                ...r,
                budget_total:    budget ?? r.budget_total ?? 0,
                budget_fund_101: r.fund_101 ?? r.budget_fund_101 ?? 0,
                budget_fund_164: r.fund_164 ?? r.budget_fund_164 ?? 0,
                budget_fund_161: r.fund_161 ?? r.budget_fund_161 ?? 0,
                budget_fund_163: r.fund_163 ?? r.budget_fund_163 ?? 0,
                pi_count: 0,
            })),
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
            <div class="flex items-center justify-between gap-3 bg-amber-50 border-b border-amber-100 px-7 py-2.5 shrink-0">
              <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <p class="text-[11px] text-amber-700 font-semibold">
                  Click <strong>Edit</strong> on any row to correct the name or budget. Sub-offices are shown indented under their parent.
                </p>
              </div>
              <button @click="openAddOffice"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#0D2137] text-white text-[11px] font-bold hover:bg-[#1A5276] transition-colors shrink-0">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Add Office
              </button>
            </div>

            <!-- Table (scrollable) -->
            <div class="overflow-y-auto flex-1">
              <table class="w-full text-[13px]">
                <thead class="sticky top-0 bg-white z-10">
                  <tr class="border-b-2 border-gray-100">
                    <th class="px-5 py-3 w-10">
                      <button @click="selectAll(selectedRows.length < previewRows.length)"
                        :class="['w-4 h-4 rounded border-2 flex items-center justify-center transition-all mx-auto',
                          selectedRows.length === previewRows.length ? 'bg-[#0D2137] border-[#0D2137]'
                          : selectedRows.length > 0 ? 'bg-[#0D2137]/30 border-[#0D2137]/40' : 'border-gray-300']">
                        <svg v-if="selectedRows.length > 0" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                          <polyline points="20 6 9 17 4 12"/>
                        </svg>
                      </button>
                    </th>
                    <th class="text-left px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-8">#</th>
                    <th class="text-left px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department / Office</th>
                    <th class="text-left px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-20">Code</th>
                    <th class="text-right px-4 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-40">Budget (₱)</th>
                    <th class="px-4 py-3 w-20"></th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="(row, gi) in groupedRows" :key="row.department + row.sheet_code">

                    <!-- Drop-into highlight bar (shows when dragging onto a parent) -->
                    <tr v-if="dragOver === row.department && dragOverPos === 'above'" class="h-0.5 bg-blue-400 opacity-60" />

                    <!-- Row -->
                    <tr
                      draggable="true"
                      @dragstart="onDragStart($event, row)"
                      @dragover="onDragOver($event, row)"
                      @dragleave="onDragLeave"
                      @drop="onDrop($event, row)"
                      @dragend="onDragEnd"
                      :class="[
                        'border-b transition-all cursor-grab active:cursor-grabbing',
                        row._isChild ? 'border-gray-50 bg-slate-50/50' : 'border-gray-100',
                        row.selected ? (row._isChild ? 'hover:bg-blue-50/20' : 'hover:bg-blue-50/30') : 'opacity-40 bg-gray-50/80',
                        dragRow?.department === row.department ? 'opacity-30 scale-[0.99]' : '',
                        dragOver === row.department && dragOverPos === 'into' ? 'ring-2 ring-inset ring-blue-400 bg-blue-50/40' : '',
                      ]">

                      <!-- Drag handle + checkbox combined -->
                      <td class="px-3 py-3 text-center w-12">
                        <div class="flex items-center gap-1.5 justify-center">
                          <!-- Drag grip -->
                          <svg class="w-3 h-3 text-gray-200 hover:text-gray-400 shrink-0 cursor-grab" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="9" cy="5"  r="1.5"/><circle cx="15" cy="5"  r="1.5"/>
                            <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                            <circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/>
                          </svg>
                          <!-- Checkbox -->
                          <button @click.stop="toggleRow(previewIndex(row))"
                            :class="['w-4 h-4 rounded border-2 flex items-center justify-center transition-all',
                              row.selected ? 'bg-[#0D2137] border-[#0D2137]' : 'border-gray-300 bg-white']">
                            <svg v-if="row.selected" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                              <polyline points="20 6 9 17 4 12"/>
                            </svg>
                          </button>
                        </div>
                      </td>

                      <!-- Number -->
                      <td class="px-3 py-3 text-gray-300 font-mono text-[10px] font-bold w-8">
                        <span v-if="!row._isChild">{{ String(previewIndex(row)+1).padStart(2,'0') }}</span>
                        <span v-else class="text-[9px] text-blue-300">{{ row.no || '·' }}</span>
                      </td>

                      <!-- Department name -->
                      <td class="py-3" :class="row._isChild ? 'pl-8 pr-4' : 'px-3'">
                        <div class="flex items-center gap-2 flex-wrap">
                          <div v-if="row._isChild" class="flex items-center gap-1 shrink-0">
                            <div class="w-3 h-px bg-gray-300"/>
                            <div class="w-1 h-1 rounded-full bg-gray-300"/>
                          </div>
                          <span v-if="!row._isChild && groupedRows.some(r => r._isChild && r.parent_dept === row.department)"
                            class="text-[8px] font-bold text-blue-500 bg-blue-50 border border-blue-100 px-1.5 py-0.5 rounded-full shrink-0 uppercase tracking-wide">
                            parent
                          </span>
                          <input v-if="row._editing"
                            v-model="previewRows[previewIndex(row)].department"
                            @keyup.enter="toggleEdit(previewIndex(row))"
                            class="flex-1 text-[13px] font-semibold text-[#0D2137] border-0 border-b-2 border-[#C9A84C] outline-none bg-transparent pb-0.5 focus:ring-0"
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
                          <span v-if="dragOver === row.department && dragOverPos === 'into' && !row._isChild"
                            class="text-[9px] font-bold text-blue-500 bg-blue-100 px-1.5 py-0.5 rounded-full animate-pulse">
                            drop here to nest →
                          </span>
                        </div>
                      </td>

                      <!-- Code -->
                      <td class="px-3 py-3">
                        <span :class="['text-[10px] font-mono font-bold px-2 py-0.5 rounded-lg border whitespace-nowrap',
                          row._isChild ? 'bg-gray-50 text-gray-400 border-gray-100' : 'bg-[#0D2137]/6 text-[#0D2137] border-[#0D2137]/10']">
                          {{ row.sheet_code || row.no || '—' }}
                        </span>
                      </td>

                      <!-- Budget -->
                      <td class="px-3 py-3 text-right">
                        <input v-if="row._editing"
                          v-model.number="previewRows[previewIndex(row)].budget_total"
                          type="number"
                          @keyup.enter="toggleEdit(previewIndex(row))"
                          class="w-32 text-right text-[13px] font-mono font-bold text-[#0D2137] border-0 border-b-2 border-[#C9A84C] outline-none bg-transparent pb-0.5"
                        />
                        <template v-else>
                          <span v-if="(row.budget_total ?? row.budget ?? 0) == 0"
                            class="text-[10px] font-bold text-amber-400 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-full">
                            No allocation
                          </span>
                          <span v-else :class="['font-mono font-bold text-[13px]', row._isChild ? 'text-gray-500' : 'text-[#0D2137]']">
                            {{ php(row.budget_total ?? row.budget ?? 0) }}
                          </span>
                        </template>
                      </td>

                      <!-- Actions: Edit + Move -->
                      <td class="px-3 py-3">
                        <div class="flex items-center gap-1 justify-end">
                          <!-- Move button (only for non-editing) -->
                          <button v-if="!row._editing" @click.stop="openMovePopup(row, previewIndex(row))"
                            class="text-[10px] font-bold px-2 py-1 rounded-lg text-gray-300 hover:text-blue-500 hover:bg-blue-50 transition-all"
                            title="Move to parent / detach">
                            ⇅
                          </button>
                          <!-- Remove from parent (x button for children) -->
                          <button v-if="row._isChild && !row._editing" @click.stop="removeFromParent(row)"
                            class="text-[10px] font-bold px-1.5 py-1 rounded-lg text-gray-200 hover:text-red-400 hover:bg-red-50 transition-all"
                            title="Remove from parent (make top-level)">
                            ✕
                          </button>
                          <!-- + Sub button (parent rows only, not while editing) -->
                          <button
                            v-if="!row._isChild && !row._editing"
                            @click.stop="openAddOffice(row.department)"
                            class="text-[10px] font-bold px-2 py-1 rounded-lg text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 border border-emerald-100 hover:border-emerald-200 transition-all whitespace-nowrap"
                            title="Add sub-office under this department">
                            + Sub
                          </button>
                          <!-- Edit/Done -->
                          <button @click.stop="toggleEdit(previewIndex(row))"
                            :class="['text-[11px] font-bold px-2.5 py-1 rounded-lg transition-all',
                              row._editing ? 'bg-emerald-100 text-emerald-700' : 'text-gray-400 hover:text-[#0D2137] hover:bg-gray-100']">
                            {{ row._editing ? '✓ Done' : 'Edit' }}
                          </button>
                        </div>
                      </td>
                    </tr>

                    <!-- Drop-below highlight -->
                    <tr v-if="dragOver === row.department && dragOverPos === 'below'" class="h-0.5 bg-blue-400 opacity-60" />
                  </template>

                  <!-- Move-to-parent popup (inline, appears below active row) -->
                  <tr v-if="showMovePopup">
                    <td colspan="6" class="px-0 py-0">
                      <div class="mx-4 my-2 bg-white border-2 border-blue-200 rounded-2xl shadow-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                          <div>
                            <p class="text-[12px] font-extrabold text-[#0D2137]">Move "{{ moveTarget?.department }}"</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">Choose a parent or make it a top-level department</p>
                          </div>
                          <button @click="showMovePopup = false" class="text-gray-300 hover:text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                          </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto">
                          <!-- Top level option -->
                          <button @click="applyMove(null)"
                            :class="['px-3 py-2 rounded-xl border-2 text-left transition-all text-[11px] font-bold',
                              !moveTarget?.parent_dept ? 'border-[#0D2137] bg-[#0D2137]/5 text-[#0D2137]' : 'border-gray-100 hover:border-blue-200 text-gray-500 hover:text-[#0D2137]']">
                            <div class="flex items-center gap-2">
                              <span class="text-base">🏛</span>
                              <div>
                                <p>Top-level department</p>
                                <p class="text-[9px] font-normal text-gray-400 mt-0.5">No parent — standalone</p>
                              </div>
                            </div>
                          </button>
                          <!-- Parent options -->
                          <button v-for="p in parentOptions.filter(p => p.department !== moveTarget?.department)" :key="p.department"
                            @click="applyMove(p.department)"
                            :class="['px-3 py-2 rounded-xl border-2 text-left transition-all',
                              moveTarget?.parent_dept === p.department
                                ? 'border-blue-400 bg-blue-50 text-[#0D2137]'
                                : 'border-gray-100 hover:border-blue-200 text-gray-600 hover:text-[#0D2137]']">
                            <div class="flex items-center gap-2">
                              <span class="text-[10px] font-mono font-bold bg-[#0D2137]/8 text-[#0D2137] px-1.5 py-0.5 rounded border border-[#0D2137]/10 shrink-0">
                                {{ p.sheet_code || p.no || '?' }}
                              </span>
                              <span class="text-[11px] font-semibold truncate">{{ p.department }}</span>
                              <span v-if="moveTarget?.parent_dept === p.department" class="text-[9px] text-blue-500 shrink-0">current</span>
                            </div>
                          </button>
                        </div>
                      </div>
                    </td>
                  </tr>
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
                    <td />
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- ── Add Office mini-modal ─────────────────────── -->
            <Transition name="modal">
              <div v-if="showAddOffice" class="absolute inset-0 z-20 flex items-center justify-center bg-[#0D2137]/40 backdrop-blur-sm rounded-3xl">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-6 p-6">
                  <div class="flex items-center justify-between mb-5">
                    <h3 class="text-[15px] font-extrabold text-[#0D2137]">Add New Office</h3>
                    <button @click="showAddOffice = false" class="text-gray-400 hover:text-gray-600">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                  </div>

                  <div class="space-y-4">
                    <!-- Office name -->
                    <div>
                      <label class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400 block mb-1">Office / Department Name *</label>
                      <input v-model="newOffice.department" type="text" placeholder="e.g. Library Services Unit"
                        class="w-full px-3 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 text-[#0D2137] font-semibold" />
                    </div>

                    <!-- Code -->
                    <div>
                      <label class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400 block mb-1">Short Code</label>
                      <input v-model="newOffice.sheet_code" type="text" placeholder="e.g. LSU (auto-generated if blank)"
                        class="w-full px-3 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 font-mono text-gray-600" />
                    </div>

                    <!-- Budget -->
                    <div>
                      <label class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400 block mb-1">Total Budget (₱)</label>
                      <input v-model.number="newOffice.budget_total" type="number" placeholder="0.00"
                        class="w-full px-3 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 font-mono text-[#0D2137]" />
                    </div>

                    <!-- Parent dept -->
                    <div>
                      <label class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400 block mb-1">Sub-Office Under (optional)</label>
                      <select v-model="newOffice.parent_dept"
                        class="w-full px-3 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 text-gray-600 bg-white">
                        <option value="">— None (top-level department) —</option>
                        <option v-for="p in parentOptions" :key="p.department" :value="p.department">
                          {{ p.department }}
                        </option>
                      </select>
                      <p class="text-[10px] text-gray-400 mt-1">Leave blank if this is a top-level department</p>
                    </div>

                    <!-- Is parent toggle -->
                    <div v-if="!newOffice.parent_dept" class="flex items-center gap-3">
                      <button @click="newOffice.is_parent = !newOffice.is_parent"
                        :class="['w-9 h-5 rounded-full transition-colors relative',
                          newOffice.is_parent ? 'bg-[#0D2137]' : 'bg-gray-200']">
                        <span :class="['absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform',
                          newOffice.is_parent ? 'translate-x-4' : 'translate-x-0.5']" />
                      </button>
                      <span class="text-[12px] text-gray-500">This office has sub-offices</span>
                    </div>
                  </div>

                  <div class="flex gap-3 mt-6">
                    <button @click="showAddOffice = false"
                      class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-[13px] font-semibold text-gray-500 hover:border-gray-300 transition-all">
                      Cancel
                    </button>
                    <button @click="saveNewOffice" :disabled="!newOffice.department.trim()"
                      class="flex-1 px-4 py-2.5 rounded-xl bg-[#0D2137] text-white text-[13px] font-bold hover:bg-[#1A5276] transition-colors disabled:opacity-40">
                      Add to List
                    </button>
                  </div>
                </div>
              </div>
            </Transition>

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