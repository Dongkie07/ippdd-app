import { ref, computed } from 'vue'
import axios from 'axios'

export function useUploadLogic() {

  // ── Core State ─────────────────────────────────────────────
  const stage    = ref('idle')  // idle | uploading | saving | done
  const file     = ref(null)
  const year     = ref(new Date().getFullYear())
  const dragging = ref(false)
  const error    = ref(null)

  // ── Modal State ────────────────────────────────────────────
  const showModal    = ref(false)
  const previewRows  = ref([])
  const filename     = ref('')
  const parseStats   = ref({})
  const savedMessage = ref('')

  // ── Computed ───────────────────────────────────────────────
  const selectedRows = computed(() => previewRows.value.filter(r => r.selected))
  const totalBudget  = computed(() =>
    selectedRows.value.reduce((s, r) => s + parseFloat(r.budget_total ?? r.budget ?? 0), 0)
  )

  // ── File Pick ──────────────────────────────────────────────
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

  // ── Step 1: Upload & Parse ─────────────────────────────────
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
        filename.value   = res.data.filename
        parseStats.value = {
          total_budget: res.data.total_budget,
          total_depts:  res.data.total_depts,
          year:         res.data.year,
        }
        stage.value     = 'idle'
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

  // ── Row Edit Helpers ───────────────────────────────────────
  const toggleRow  = (i) => previewRows.value[i].selected = !previewRows.value[i].selected
  const toggleEdit = (i) => previewRows.value[i]._editing  = !previewRows.value[i]._editing
  const selectAll  = (v) => previewRows.value.forEach(r => r.selected = v)

  // ── Add Office ─────────────────────────────────────────────
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
      sheet_code:   newOffice.value.sheet_code.trim() || newOffice.value.department.slice(0, 6).toUpperCase(),
      budget: budget, budget_total: budget,
      parent_dept: newOffice.value.parent_dept || null,
      is_parent: newOffice.value.is_parent,
      selected: true, _editing: false,
      fund_101: 0, fund_164: 0, fund_161: 0, fund_163: 0,
      budget_fund_101: 0, budget_fund_164: 0, budget_fund_161: 0, budget_fund_163: 0,
    })
    showAddOffice.value = false
  }

  // ── Move-to-parent ─────────────────────────────────────────
  const moveTarget    = ref(null)
  const moveTargetIdx = ref(-1)
  const showMovePopup = ref(false)

  const openMovePopup = (row, idx) => {
    moveTarget.value    = row
    moveTargetIdx.value = idx
    showMovePopup.value = true
  }
  const applyMove = (newParent) => {
    const idx = previewRows.value.findIndex(
      r => r.department === moveTarget.value.department && r.sheet_code === moveTarget.value.sheet_code
    )
    if (idx !== -1) {
      previewRows.value[idx].parent_dept = newParent || null
      previewRows.value[idx].is_parent   = false
      if (newParent) {
        const pIdx = previewRows.value.findIndex(r => r.department === newParent)
        if (pIdx !== -1) previewRows.value[pIdx].is_parent = true
      }
    }
    showMovePopup.value = false
  }
  const removeFromParent = (row) => {
    const idx = previewRows.value.findIndex(
      r => r.department === row.department && r.sheet_code === row.sheet_code
    )
    if (idx !== -1) previewRows.value[idx].parent_dept = null
  }

  // ── Drag & Drop Rows ───────────────────────────────────────
  const dragRow     = ref(null)
  const dragOver    = ref(null)
  const dragOverPos = ref(null)

  const onDragStart = (e, row) => {
    dragRow.value = row
    e.dataTransfer.effectAllowed = 'move'
  }
  const onDragOver = (e, row) => {
    e.preventDefault()
    if (!dragRow.value || dragRow.value.department === row.department) return
    dragOver.value = row.department
    const rect = e.currentTarget.getBoundingClientRect()
    const pct  = (e.clientY - rect.top) / rect.height
    if (!row._isChild && pct > 0.25 && pct < 0.75) {
      dragOverPos.value = 'into'
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
    const srcIdx = previewRows.value.findIndex(
      r => r.department === dragRow.value.department && r.sheet_code === dragRow.value.sheet_code
    )
    if (srcIdx === -1) { dragRow.value = null; dragOver.value = null; return }

    if (dragOverPos.value === 'into' && !targetRow._isChild) {
      previewRows.value[srcIdx].parent_dept = targetRow.department
      previewRows.value[srcIdx].is_parent   = false
      const pIdx = previewRows.value.findIndex(r => r.department === targetRow.department)
      if (pIdx !== -1) previewRows.value[pIdx].is_parent = true
    } else {
      const [moved] = previewRows.value.splice(srcIdx, 1)
      const newIdx  = previewRows.value.findIndex(
        r => r.department === targetRow.department && r.sheet_code === targetRow.sheet_code
      )
      const insertAt = dragOverPos.value === 'above' ? newIdx : newIdx + 1
      previewRows.value.splice(insertAt, 0, moved)
    }
    dragRow.value = null; dragOver.value = null; dragOverPos.value = null
  }
  const onDragEnd = () => { dragRow.value = null; dragOver.value = null }

  // ── Grouped Rows (parents + children) ─────────────────────
  const groupedRows = computed(() => {
    const parents = previewRows.value.filter(r => !r.parent_dept)
    const result  = []
    parents.forEach(p => {
      result.push({ ...p, _isChild: false })
      previewRows.value
        .filter(r => r.parent_dept === p.department)
        .forEach(c => result.push({ ...c, _isChild: true }))
    })
    const inResult = new Set(result.map(r => r.department + r.sheet_code))
    previewRows.value.forEach(r => {
      if (!inResult.has(r.department + r.sheet_code))
        result.push({ ...r, _isChild: !!r.parent_dept })
    })
    return result
  })

  const previewIndex = (row) =>
    previewRows.value.findIndex(
      r => r.department === row.department && r.sheet_code === row.sheet_code
    )

  // ── Step 2: Confirm & Save ─────────────────────────────────
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

  // ── Reset ──────────────────────────────────────────────────
  const reset = () => {
    stage.value       = 'idle'
    file.value        = null
    error.value       = null
    showModal.value   = false
    previewRows.value = []
  }

  // ── Format Helpers ─────────────────────────────────────────
  const php  = v => '₱' + parseFloat(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })
  const phpM = v => '₱' + (parseFloat(v || 0) / 1e6).toFixed(2) + 'M'

  return {
    // state
    stage, file, year, dragging, error,
    showModal, previewRows, filename, parseStats, savedMessage,
    // computed
    selectedRows, totalBudget, groupedRows, parentOptions,
    // file
    onPick, uploadAndParse,
    // row helpers
    toggleRow, toggleEdit, selectAll, previewIndex,
    // add office
    showAddOffice, newOffice, openAddOffice, saveNewOffice,
    // move popup
    moveTarget, moveTargetIdx, showMovePopup, openMovePopup, applyMove, removeFromParent,
    // drag & drop
    dragRow, dragOver, dragOverPos,
    onDragStart, onDragOver, onDragLeave, onDrop, onDragEnd,
    // actions
    confirmImport, reset,
    // format
    php, phpM,
  }
}
