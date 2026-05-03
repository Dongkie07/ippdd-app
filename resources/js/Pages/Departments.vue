<script setup>
/**
 * Pages/Departments.vue — Manual Data Entry
 * Full CRUD: add, edit, delete departments + fiscal year management
 */
import { ref, computed } from 'vue'
import { router }        from '@inertiajs/vue3'
import AppLayout         from '@/Layouts/AppLayout.vue'
import { useFormatters } from '@/composables/useFormatters'

const props = defineProps({
  years:       { type: Array,  default: () => [] },
  deptsByYear: { type: Object, default: () => ({}) },
})

const { php, phpM } = useFormatters()

// ── State ─────────────────────────────────────────────────────
const activeYear    = ref(props.years[0] ?? null)
const saving        = ref(false)
const feedback      = ref(null)
const confirmDelete = ref(null)

// ── Form ──────────────────────────────────────────────────────
const BLANK = {
  id: null, year: null, department: '', sheet_code: '',
  parent_dept: '', is_parent: false, status: 'Pending', remarks: '',
  budget_fund_101: 0, budget_fund_164: 0,
  budget_fund_161: 0, budget_fund_163: 0,
}
const form     = ref({ ...BLANK })
const showForm = ref(false)
const formMode = ref('add')

// ── New year form ─────────────────────────────────────────────
const showYearForm = ref(false)
const newYearForm  = ref({ year: new Date().getFullYear() + 1, copy_from: '' })

// ── Computed ──────────────────────────────────────────────────
const rows = computed(() => props.deptsByYear[activeYear.value] ?? [])
const parentOptions = computed(() => rows.value.filter(r => !r.parent_dept))
const totalBudget   = computed(() =>
  rows.value.filter(r => !r.parent_dept).reduce((s, r) => s + (r.budget_total ?? 0), 0)
)
const formTotal = computed(() =>
  ['budget_fund_101','budget_fund_164','budget_fund_161','budget_fund_163']
    .reduce((s, k) => s + (parseFloat(form.value[k]) || 0), 0)
)

const FUNDS = [
  { key: 'budget_fund_101', label: 'Fund 101', color: '#0D2137' },
  { key: 'budget_fund_164', label: 'Fund 164', color: '#C9A84C' },
  { key: 'budget_fund_161', label: 'Fund 161', color: '#1E8449' },
  { key: 'budget_fund_163', label: 'Fund 163', color: '#2E86C1' },
]

// ── Helpers ───────────────────────────────────────────────────

// Read CSRF token from meta tag (Laravel injects this in app.blade.php)
const csrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

// Read XSRF-TOKEN from cookie (Laravel sets this automatically)
const xsrfCookie = () => {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : ''
}

const toast = (type, message) => {
  feedback.value = { type, message }
  setTimeout(() => feedback.value = null, 4000)
}

// Refresh page data via Inertia (no full reload)
const refresh = () => router.visit('/departments', { preserveScroll: true })

// ── Generic fetch helper ──────────────────────────────────────
const api = async (url, method, body = null) => {
  // Try cookie first, fall back to meta tag
  const token = xsrfCookie() || csrfToken()

  const opts = {
    method,
    credentials: 'same-origin',
    headers: {
      'Content-Type':     'application/json',
      'Accept':           'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN':     token,
      'X-XSRF-TOKEN':     token,
    },
  }
  if (body) opts.body = JSON.stringify(body)

  const res = await fetch(url, opts)

  // 419 = Laravel CSRF token expired
  if (res.status === 419) {
    toast('error', 'Session expired — please refresh the page (F5).')
    return { success: false, message: 'CSRF token expired.' }
  }

  return await res.json()
}

// ── Open forms ────────────────────────────────────────────────
const openAdd = () => {
  form.value = { ...BLANK, year: activeYear.value }
  formMode.value = 'add'
  showForm.value = true
}

const openEdit = (row) => {
  form.value = {
    id:              row.id,
    year:            row.year ?? activeYear.value,
    department:      row.department ?? '',
    sheet_code:      row.sheet_code ?? '',
    parent_dept:     row.parent_dept ?? '',
    is_parent:       !!row.is_parent,
    status:          row.status ?? 'Pending',
    remarks:         row.remarks ?? '',
    budget_fund_101: row.budget_fund_101 ?? 0,
    budget_fund_164: row.budget_fund_164 ?? 0,
    budget_fund_161: row.budget_fund_161 ?? 0,
    budget_fund_163: row.budget_fund_163 ?? 0,
  }
  formMode.value = 'edit'
  showForm.value = true
}

// ── Submit add/edit ───────────────────────────────────────────
const submitForm = async () => {
  if (!form.value.department.trim()) return
  saving.value = true
  try {
    const isEdit = formMode.value === 'edit'
    const url    = isEdit ? `/departments/${form.value.id}` : '/departments'
    const data   = await api(url, isEdit ? 'PUT' : 'POST', form.value)
    if (data.success) {
      toast('success', data.message)
      showForm.value = false
      refresh()
    } else {
      const msg = data.errors
        ? Object.values(data.errors).flat().join(' ')
        : (data.message ?? 'Unknown error')
      toast('error', msg)
    }
  } catch (e) {
    toast('error', 'Network error — please try again.')
  } finally {
    saving.value = false
  }
}

// ── Delete department ─────────────────────────────────────────
const deleteDept = async () => {
  if (!confirmDelete.value?.id) return
  saving.value = true
  try {
    const data = await api(`/departments/${confirmDelete.value.id}`, 'DELETE')
    toast(data.success ? 'success' : 'error', data.message)
    confirmDelete.value = null
    if (data.success) refresh()
  } finally {
    saving.value = false
  }
}

// ── Delete year ───────────────────────────────────────────────
const deleteYear = async () => {
  if (!confirmDelete.value?.year) return
  saving.value = true
  try {
    const data = await api(`/departments/year/${confirmDelete.value.year}`, 'DELETE')
    toast(data.success ? 'success' : 'error', data.message)
    confirmDelete.value = null
    if (data.success) {
      activeYear.value = props.years.find(y => y !== confirmDelete.value?.year) ?? null
      refresh()
    }
  } finally {
    saving.value = false
  }
}

// ── Create new year ───────────────────────────────────────────
const submitNewYear = async () => {
  if (!newYearForm.value.year) return
  saving.value = true
  try {
    const data = await api('/departments/year', 'POST', newYearForm.value)
    toast(data.success ? 'success' : 'error', data.message)
    if (data.success) {
      showYearForm.value = false
      activeYear.value   = newYearForm.value.year
      refresh()
    }
  } finally {
    saving.value = false
  }
}

// ── Style helpers ─────────────────────────────────────────────
const statusClass = (s) =>
  s === 'Approved'     ? 'text-emerald-700 bg-emerald-50 border-emerald-100' :
  s === 'For Revision' ? 'text-red-600 bg-red-50 border-red-100' :
                         'text-amber-700 bg-amber-50 border-amber-100'
</script>

<template>
  <AppLayout>
    <template #breadcrumb>Manual Entry</template>
    <template #title>Manual Data Entry</template>
    <template #subtitle>Add, edit, or delete department budget data for any fiscal year</template>

    <div class="space-y-5">

      <!-- ── Feedback toast ─────────────────────────────────── -->
      <Transition name="toast">
        <div v-if="feedback" :class="[
          'fixed top-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5',
          'rounded-2xl shadow-xl border text-[13px] font-semibold max-w-sm',
          feedback.type === 'success'
            ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
            : 'bg-red-50 border-red-200 text-red-800'
        ]">
          <span class="text-lg">{{ feedback.type === 'success' ? '✓' : '✕' }}</span>
          <span>{{ feedback.message }}</span>
        </div>
      </Transition>

      <!-- ── Year tabs + action buttons ────────────────────── -->
      <div class="flex items-center justify-between flex-wrap gap-3">

        <!-- Year pills -->
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex gap-1 bg-gray-100 rounded-2xl p-1">
            <button v-for="yr in years" :key="yr" @click="activeYear = yr"
              :class="['px-4 py-2 rounded-xl text-[13px] font-bold transition-all',
                activeYear === yr
                  ? 'bg-white text-[#0D2137] shadow-sm'
                  : 'text-gray-400 hover:text-gray-600']">
              {{ yr }}
            </button>
          </div>

          <!-- + New Year button -->
          <button @click="showYearForm = true"
            class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-dashed
                   border-gray-300 text-gray-400 hover:border-[#0D2137] hover:text-[#0D2137]
                   transition-all text-[12px] font-bold">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path d="M12 5v14M5 12h14"/>
            </svg>
            New Year
          </button>
        </div>

        <!-- Right actions -->
        <div class="flex items-center gap-2">
          <button v-if="activeYear"
            @click="confirmDelete = { year: activeYear }"
            class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-[12px] font-bold
                   text-red-400 hover:text-red-600 hover:bg-red-50 border border-red-100
                   transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6l-1 14H6L5 6M10 11v6M14 11v6"/>
            </svg>
            Delete FY {{ activeYear }}
          </button>

          <button @click="openAdd"
            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-[#0D2137] text-white
                   text-[13px] font-bold hover:bg-[#1A5276] transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Department
          </button>
        </div>
      </div>

      <!-- ── Summary bar ────────────────────────────────────── -->
      <div v-if="activeYear"
        class="bg-white rounded-2xl border border-gray-200 shadow-sm px-6 py-4
               flex items-center justify-between flex-wrap gap-4">
        <div>
          <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">
            FY {{ activeYear }} — Total Budget
          </p>
          <p class="text-[24px] font-extrabold text-[#0D2137] mt-0.5">{{ php(totalBudget) }}</p>
        </div>
        <div class="flex items-center gap-5 flex-wrap">
          <div v-for="f in FUNDS" :key="f.key" class="text-right">
            <p class="text-[9px] font-extrabold uppercase tracking-widest" :style="{ color: f.color }">
              {{ f.label }}
            </p>
            <p class="text-[13px] font-bold text-[#0D2137]">
              {{ phpM(rows.filter(r => !r.parent_dept).reduce((s,r) => s + (r[f.key]??0), 0)) }}
            </p>
          </div>
          <div class="border-l border-gray-100 pl-5 text-right">
            <p class="text-[9px] font-extrabold uppercase tracking-widest text-gray-400">Top-level</p>
            <p class="text-[13px] font-bold text-[#0D2137]">
              {{ rows.filter(r => !r.parent_dept).length }} offices
            </p>
          </div>
        </div>
      </div>

      <!-- ── Empty state ────────────────────────────────────── -->
      <div v-if="!activeYear"
        class="bg-white rounded-2xl border-2 border-dashed border-gray-200 py-20 text-center">
        <p class="text-gray-300 text-sm mb-3">No fiscal years yet</p>
        <button @click="showYearForm = true"
          class="px-4 py-2 rounded-xl bg-[#0D2137] text-white text-[13px] font-bold">
          Create First Year
        </button>
      </div>

      <!-- ── Department table ───────────────────────────────── -->
      <div v-else class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-[12px]">
            <thead>
              <tr class="border-b-2 border-gray-100 bg-gray-50/60">
                <th class="text-left px-5 py-3.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400 w-10">#</th>
                <th class="text-left px-4 py-3.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Department / Office</th>
                <th class="text-right px-4 py-3.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Total</th>
                <th v-for="f in FUNDS" :key="f.key"
                  class="text-right px-4 py-3.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">
                  {{ f.label }}
                </th>
                <th class="text-center px-4 py-3.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Status</th>
                <!-- Fixed-width action column — no overflow -->
                <th class="px-4 py-3.5 w-28"></th>
              </tr>
            </thead>
            <tbody>
              <template v-for="row in rows" :key="row.id">
                <tr :class="[
                  'border-b border-gray-50 transition-colors',
                  row.parent_dept
                    ? 'hover:bg-slate-50 bg-slate-50/30'
                    : 'hover:bg-[#0D2137]/[0.02]'
                ]">
                  <!-- NO -->
                  <td class="px-5 py-3 text-gray-300 font-mono text-[10px] font-bold">
                    {{ row.no || '—' }}
                  </td>

                  <!-- Name -->
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2 min-w-0">
                      <template v-if="row.parent_dept">
                        <div class="flex items-center gap-1 shrink-0">
                          <div class="w-4 h-px bg-gray-200"/>
                          <div class="w-1.5 h-1.5 rounded-full bg-gray-300"/>
                        </div>
                      </template>
                      <span :class="[
                        'truncate font-semibold',
                        row.parent_dept
                          ? 'text-gray-500 text-[12px]'
                          : 'text-[#0D2137] text-[13px]'
                      ]">{{ row.department }}</span>
                      <span v-if="row.is_parent"
                        class="text-[8px] font-bold text-blue-500 bg-blue-50 border
                               border-blue-100 px-1.5 py-0.5 rounded-full shrink-0">
                        parent
                      </span>
                      <span v-if="row.parent_dept"
                        class="text-[9px] text-gray-300 bg-gray-50 px-1.5 py-0.5
                               rounded font-mono shrink-0 hidden xl:inline">
                        ↳ {{ row.parent_dept }}
                      </span>
                    </div>
                  </td>

                  <!-- Total -->
                  <td class="px-4 py-3 text-right font-mono font-bold text-[#0D2137]">
                    {{ (row.budget_total ?? 0) > 0 ? php(row.budget_total) : '—' }}
                  </td>

                  <!-- Fund cols -->
                  <td v-for="f in FUNDS" :key="f.key"
                    class="px-4 py-3 text-right font-mono text-gray-400 text-[11px]">
                    {{ (row[f.key] ?? 0) > 0 ? php(row[f.key]) : '—' }}
                  </td>

                  <!-- Status -->
                  <td class="px-4 py-3 text-center">
                    <span :class="[
                      'text-[10px] font-bold px-2 py-0.5 rounded-full border whitespace-nowrap',
                      statusClass(row.status)
                    ]">{{ row.status }}</span>
                  </td>

                  <!-- Actions — fixed width, no overlap -->
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-1 justify-end shrink-0">
                      <button @click="openEdit(row)"
                        class="text-[11px] font-bold px-2.5 py-1.5 rounded-lg
                               text-gray-400 hover:text-[#0D2137] hover:bg-gray-100
                               transition-all whitespace-nowrap">
                        Edit
                      </button>
                      <button @click="confirmDelete = { id: row.id, name: row.department }"
                        class="p-1.5 rounded-lg text-gray-200 hover:text-red-500
                               hover:bg-red-50 transition-all shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                          stroke-width="2" viewBox="0 0 24 24">
                          <polyline points="3 6 5 6 21 6"/>
                          <path d="M19 6l-1 14H6L5 6M10 11v6M14 11v6"/>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <tr v-if="rows.length === 0">
                <td colspan="9" class="px-5 py-16 text-center">
                  <p class="text-gray-300 text-sm mb-3">No departments for FY {{ activeYear }} yet.</p>
                  <button @click="openAdd"
                    class="px-4 py-2 rounded-xl bg-[#0D2137] text-white text-[12px] font-bold
                           hover:bg-[#1A5276] transition-colors">
                    + Add First Department
                  </button>
                </td>
              </tr>
            </tbody>

            <!-- Footer totals -->
            <tfoot v-if="rows.length > 0">
              <tr class="border-t-2 border-gray-100 bg-[#0D2137]/[0.025]">
                <td colspan="2" class="px-5 py-3">
                  <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">
                    Grand Total — {{ rows.filter(r => !r.parent_dept).length }} top-level offices
                  </span>
                </td>
                <td class="px-4 py-3 text-right font-mono font-extrabold text-[#0D2137] text-[13px]">
                  {{ php(totalBudget) }}
                </td>
                <td v-for="f in FUNDS" :key="f.key"
                  class="px-4 py-3 text-right font-mono font-bold text-[#0D2137] text-[12px]">
                  {{ php(rows.filter(r => !r.parent_dept).reduce((s,r) => s + (r[f.key]??0), 0)) }}
                </td>
                <td colspan="2" />
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

    </div>

    <!-- ══ ADD / EDIT SLIDE-IN PANEL ══════════════════════════ -->
    <Transition name="panel">
      <div v-if="showForm"
        class="fixed inset-0 z-40 flex justify-end">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/20 backdrop-blur-sm"
          @click="showForm = false" />

        <!-- Panel -->
        <div class="relative z-10 w-full max-w-md bg-white shadow-2xl flex flex-col h-full">

          <!-- Header -->
          <div class="flex items-center justify-between px-7 py-5 border-b border-gray-100 shrink-0">
            <div>
              <h2 class="text-[16px] font-extrabold text-[#0D2137]">
                {{ formMode === 'add' ? '+ Add Department' : 'Edit Department' }}
              </h2>
              <p class="text-[11px] text-gray-400 mt-0.5">Fiscal Year {{ form.year }}</p>
            </div>
            <button @click="showForm = false"
              class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200
                     flex items-center justify-center transition-colors">
              <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                stroke-width="2.5" viewBox="0 0 24 24">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>

          <!-- Scrollable body -->
          <div class="flex-1 overflow-y-auto px-7 py-6 space-y-5">

            <!-- Department name -->
            <div>
              <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Department / Office Name *</label>
              <input v-model="form.department" type="text"
                placeholder="e.g. Learning Resource Division"
                class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 text-gray-700 bg-white" />
            </div>

            <!-- Code + Parent -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Short Code</label>
                <input v-model="form.sheet_code" type="text" placeholder="e.g. 1a"
                  class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 text-gray-700 bg-white font-mono" />
              </div>
              <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Sub-office Under</label>
                <select v-model="form.parent_dept" class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 text-gray-700 bg-white">
                  <option value="">— Top-level —</option>
                  <option v-for="p in parentOptions" :key="p.id" :value="p.department">
                    {{ p.department.length > 30 ? p.department.slice(0,29)+'…' : p.department }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Budget inputs -->
            <div>
              <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Budget Allocation (₱)</label>
              <div class="grid grid-cols-2 gap-3">
                <div v-for="f in FUNDS" :key="f.key">
                  <label class="block text-[10px] font-bold mb-1" :style="{ color: f.color }">
                    {{ f.label }}
                  </label>
                  <input v-model.number="form[f.key]" type="number" min="0" step="0.01"
                    class="w-full px-3 py-2 text-[12px] border border-gray-200 rounded-xl
                           focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20
                           font-mono text-gray-700 bg-gray-50/50" />
                </div>
              </div>

              <!-- Live computed total -->
              <div class="mt-3 flex items-center justify-between
                          bg-[#0D2137]/5 rounded-xl px-4 py-3 border border-[#0D2137]/10">
                <span class="text-[11px] font-bold text-gray-500">Computed Total</span>
                <span class="text-[15px] font-extrabold text-[#0D2137]">{{ php(formTotal) }}</span>
              </div>
            </div>

            <!-- Status + Is Parent toggle -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Status</label>
                <select v-model="form.status" class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 text-gray-700 bg-white">
                  <option>Approved</option>
                  <option>Pending</option>
                  <option>For Revision</option>
                </select>
              </div>
              <div class="flex flex-col justify-end pb-1">
                <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Has Sub-offices</label>
                <button @click="form.is_parent = !form.is_parent"
                  class="flex items-center gap-2.5 mt-1">
                  <div :class="['w-10 h-5 rounded-full relative transition-colors shrink-0',
                    form.is_parent ? 'bg-[#0D2137]' : 'bg-gray-200']">
                    <span :class="['absolute top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform',
                      form.is_parent ? 'translate-x-5' : 'translate-x-0.5']" />
                  </div>
                  <span class="text-[12px] text-gray-500">
                    {{ form.is_parent ? 'Yes' : 'No' }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Remarks -->
            <div>
              <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Remarks <span class="text-gray-300 font-normal">(optional)</span></label>
              <textarea v-model="form.remarks" rows="3"
                placeholder="Notes, observations, or comments…"
                class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 text-gray-700 bg-white resize-none" />
            </div>
          </div>

          <!-- Footer buttons -->
          <div class="px-7 py-5 border-t border-gray-100 flex gap-3 shrink-0">
            <button @click="showForm = false"
              class="flex-1 py-2.5 rounded-xl border border-gray-200
                     text-[13px] font-semibold text-gray-500
                     hover:border-gray-300 transition-all">
              Cancel
            </button>
            <button @click="submitForm"
              :disabled="saving || !form.department.trim()"
              class="flex-1 py-2.5 rounded-xl bg-[#0D2137] text-white
                     text-[13px] font-bold hover:bg-[#1A5276]
                     transition-colors disabled:opacity-40">
              {{ saving ? 'Saving…' : (formMode === 'add' ? 'Add Department' : 'Save Changes') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══ NEW YEAR MODAL ══════════════════════════════════════ -->
    <Transition name="modal">
      <div v-if="showYearForm"
        class="fixed inset-0 z-40 flex items-center justify-center
               bg-black/30 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm">
          <div class="px-7 pt-6 pb-4 border-b border-gray-100">
            <h2 class="text-[16px] font-extrabold text-[#0D2137]">Create Fiscal Year</h2>
            <p class="text-[11px] text-gray-400 mt-0.5">Blank year or copy structure from an existing one</p>
          </div>

          <div class="px-7 py-5 space-y-4">
            <div>
              <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Fiscal Year *</label>
              <input v-model.number="newYearForm.year"
                type="number" min="2024" max="2099"
                class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 text-gray-700 bg-white text-center text-[18px] font-extrabold text-[#0D2137]" />
            </div>
            <div>
              <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-1.5">Copy Departments From <span class="text-gray-300 font-normal">(optional)</span></label>
              <select v-model="newYearForm.copy_from" class="w-full px-3.5 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0D2137]/20 text-gray-700 bg-white">
                <option value="">— Start blank —</option>
                <option v-for="yr in years" :key="yr" :value="yr">FY {{ yr }}</option>
              </select>
              <p class="text-[10px] text-gray-400 mt-1.5 leading-relaxed">
                {{ newYearForm.copy_from
                  ? `Copies all ${(props.deptsByYear[newYearForm.copy_from] ?? []).length} departments from FY ${newYearForm.copy_from} with ₱0 budgets`
                  : 'Creates an empty year — add departments manually or upload a WFP file' }}
              </p>
            </div>
          </div>

          <div class="px-7 pb-6 flex gap-3">
            <button @click="showYearForm = false"
              class="flex-1 py-2.5 rounded-xl border border-gray-200
                     text-[13px] font-semibold text-gray-500">
              Cancel
            </button>
            <button @click="submitNewYear" :disabled="saving || !newYearForm.year"
              class="flex-1 py-2.5 rounded-xl bg-[#0D2137] text-white
                     text-[13px] font-bold hover:bg-[#1A5276]
                     transition-colors disabled:opacity-40">
              {{ saving ? 'Creating…' : 'Create Year' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══ CONFIRM DELETE ══════════════════════════════════════ -->
    <Transition name="modal">
      <div v-if="confirmDelete"
        class="fixed inset-0 z-50 flex items-center justify-center
               bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-8">
          <div class="w-14 h-14 rounded-full bg-red-100 flex items-center
                      justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor"
              stroke-width="2" viewBox="0 0 24 24">
              <path d="M12 9v4M12 17h.01"/>
              <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
          </div>

          <h3 class="text-[15px] font-extrabold text-[#0D2137] text-center mb-2">
            Confirm Delete
          </h3>

          <p class="text-[12px] text-gray-500 text-center leading-relaxed">
            <template v-if="confirmDelete.year">
              This will permanently delete <strong>all data for
              FY {{ confirmDelete.year }}</strong>
              ({{ rows.length }} records). This cannot be undone.
            </template>
            <template v-else>
              Delete <strong>"{{ confirmDelete.name }}"</strong> and all
              its sub-offices? This cannot be undone.
            </template>
          </p>

          <div class="flex gap-3 mt-6">
            <button @click="confirmDelete = null"
              class="flex-1 py-2.5 rounded-xl border border-gray-200
                     text-[13px] font-semibold text-gray-500">
              Cancel
            </button>
            <button
              @click="confirmDelete.year ? deleteYear() : deleteDept()"
              :disabled="saving"
              class="flex-1 py-2.5 rounded-xl bg-red-500 text-white
                     text-[13px] font-bold hover:bg-red-600
                     transition-colors disabled:opacity-40">
              {{ saving ? 'Deleting…' : 'Yes, Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </AppLayout>
</template>

<style scoped>
/* Transition animations */

/* Slide-in panel */
.panel-enter-active, .panel-leave-active { transition: transform 0.25s ease, opacity 0.25s; }
.panel-enter-from, .panel-leave-to       { transform: translateX(100%); opacity: 0; }

/* Modal fade + scale */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s, transform 0.2s; }
.modal-enter-from, .modal-leave-to       { opacity: 0; transform: scale(0.96); }

/* Toast slide down */
.toast-enter-active, .toast-leave-active { transition: opacity 0.3s, transform 0.3s; }
.toast-enter-from, .toast-leave-to       { opacity: 0; transform: translateY(-10px); }
</style>