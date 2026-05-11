/**
 * useDepartments.js
 * Manual Entry behavior stays here so the Vue page remains small.
 */
import { computed, ref, watch, watchEffect } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { FUNDS as WFP_FUNDS } from '@/constants/wfp'
import { resolveOfficeNameForYear } from '@/composables/offices/useOfficeNameHistory'

export const DEPARTMENT_FUNDS = WFP_FUNDS.map((fund) => ({
  key: fund.dbField,
  label: fund.shortLabel.replace(' — GAA', ''),
  color: fund.color,
}))

const FEEDBACK_TIMEOUT_MS = 4000

const BLANK_FORM = {
  id: null,
  office_id: '',
  year: null,
  department: '',
  sheet_code: '',
  parent_dept: '',
  is_parent: false,
  status: 'Pending',
  remarks: '',
  budget_fund_101: 0,
  budget_fund_164: 0,
  budget_fund_161: 0,
  budget_fund_163: 0,
}

const normalizeDepartment = (row, activeYear) => ({
  id: row.id,
  office_id: row.office_id ?? '',
  year: row.year ?? activeYear,
  department: row.department ?? '',
  sheet_code: row.sheet_code ?? '',
  parent_dept: row.parent_dept ?? '',
  is_parent: Boolean(row.is_parent),
  status: row.status ?? 'Pending',
  remarks: row.remarks ?? '',
  budget_fund_101: row.budget_fund_101 ?? 0,
  budget_fund_164: row.budget_fund_164 ?? 0,
  budget_fund_161: row.budget_fund_161 ?? 0,
  budget_fund_163: row.budget_fund_163 ?? 0,
})

const collectErrors = (errors) => {
  if (!errors) return 'Something went wrong.'
  return Object.values(errors).flat().join(' ')
}

export function useDepartments(props) {
  const page = usePage()
  const activeYear = ref(props.years?.[0] ?? null)
  const saving = ref(false)
  const feedback = ref(null)
  const confirmDelete = ref(null)
  const form = ref({ ...BLANK_FORM })
  const showForm = ref(false)
  const formMode = ref('add')
  const showYearForm = ref(false)
  const newYearForm = ref({ year: new Date().getFullYear() + 1, copy_from: '' })

  const years = computed(() => props.years ?? [])
  const rows = computed(() => props.deptsByYear?.[activeYear.value] ?? [])
  const offices = computed(() => props.offices ?? [])
  const parentOptions = computed(() => rows.value.filter((row) => !row.parent_dept))
  const topLevelRows = computed(() => rows.value.filter((row) => !row.parent_dept))
  const totalBudget = computed(() => topLevelRows.value.reduce((sum, row) => sum + (row.budget_total ?? 0), 0))

  const fundTotals = computed(() => DEPARTMENT_FUNDS.map((fund) => ({
    ...fund,
    value: topLevelRows.value.reduce((sum, row) => sum + (row[fund.key] ?? 0), 0),
  })))

  const formTotal = computed(() => DEPARTMENT_FUNDS.reduce((sum, fund) => (
    sum + (parseFloat(form.value[fund.key]) || 0)
  ), 0))

  const selectedOffice = computed(() => offices.value.find((office) => office.id === Number(form.value.office_id)))
  const selectedOfficeHistoricalName = computed(() => resolveOfficeNameForYear(selectedOffice.value, form.value.year))

  const toast = (type, message) => {
    feedback.value = { type, message }
    window.setTimeout(() => { feedback.value = null }, FEEDBACK_TIMEOUT_MS)
  }

  watchEffect(() => {
    const flash = page.props.flash
    if (flash?.message) toast('success', flash.message)
  })

  watch(years, (currentYears) => {
    if (!currentYears.length) {
      activeYear.value = null
      return
    }

    if (!currentYears.includes(activeYear.value)) {
      activeYear.value = currentYears[0]
    }
  })

  const submit = (method, url, data = null) => new Promise((resolve) => {
    saving.value = true
    router[method](url, data, {
      preserveScroll: true,
      onSuccess: () => resolve({ success: true }),
      onError: (errors) => resolve({ success: false, errors }),
      onFinish: () => { saving.value = false },
    })
  })

  const destroy = (url) => new Promise((resolve) => {
    saving.value = true
    router.delete(url, {
      preserveScroll: true,
      onSuccess: () => resolve({ success: true }),
      onError: (errors) => resolve({ success: false, errors }),
      onFinish: () => { saving.value = false },
    })
  })

  const openAdd = () => {
    form.value = { ...BLANK_FORM, year: activeYear.value }
    formMode.value = 'add'
    showForm.value = true
  }

  const openEdit = (row) => {
    form.value = normalizeDepartment(row, activeYear.value)
    formMode.value = 'edit'
    showForm.value = true
  }

  const selectOffice = (officeId) => {
    form.value.office_id = officeId || ''

    if (!officeId) return

    const office = offices.value.find((item) => item.id === Number(officeId))
    form.value.department = resolveOfficeNameForYear(office, form.value.year)
  }

  const closeForm = () => { showForm.value = false }
  const openYearForm = () => { showYearForm.value = true }
  const closeYearForm = () => { showYearForm.value = false }
  const askDeleteYear = () => { confirmDelete.value = { year: activeYear.value } }
  const askDeleteDepartment = (row) => { confirmDelete.value = { id: row.id, name: row.department } }
  const cancelDelete = () => { confirmDelete.value = null }

  const submitForm = async () => {
    if (!form.value.department.trim()) return

    const isEdit = formMode.value === 'edit'
    const url = isEdit ? `/departments/${form.value.id}` : '/departments'
    const result = await submit(isEdit ? 'put' : 'post', url, form.value)

    if (result.success) {
      toast('success', isEdit ? 'Department updated.' : 'Department added.')
      closeForm()
      return
    }

    toast('error', collectErrors(result.errors))
  }

  const deleteDepartment = async () => {
    if (!confirmDelete.value?.id) return

    const result = await destroy(`/departments/${confirmDelete.value.id}`)
    if (result.success) {
      toast('success', 'Department deleted.')
      cancelDelete()
      return
    }

    toast('error', 'Could not delete department.')
  }

  const deleteYear = async () => {
    if (!confirmDelete.value?.year) return

    const year = confirmDelete.value.year
    const result = await destroy(`/departments/year/${year}`)

    if (result.success) {
      toast('success', `FY ${year} deleted.`)
      cancelDelete()
      activeYear.value = years.value.find((item) => item !== year) ?? null
      return
    }

    toast('error', 'Could not delete fiscal year.')
  }

  const submitNewYear = async () => {
    if (!newYearForm.value.year) return

    const requestedYear = newYearForm.value.year
    const result = await submit('post', '/departments/year', newYearForm.value)

    if (result.success) {
      toast('success', `FY ${requestedYear} created successfully.`)
      closeYearForm()
      activeYear.value = requestedYear
      return
    }

    toast('error', collectErrors(result.errors) || 'Could not create fiscal year.')
  }

  return {
    activeYear,
    saving,
    feedback,
    confirmDelete,
    form,
    showForm,
    formMode,
    showYearForm,
    newYearForm,
    years,
    rows,
    offices,
    parentOptions,
    totalBudget,
    fundTotals,
    formTotal,
    selectedOffice,
    selectedOfficeHistoricalName,
    funds: DEPARTMENT_FUNDS,
    openAdd,
    openEdit,
    selectOffice,
    closeForm,
    openYearForm,
    closeYearForm,
    askDeleteYear,
    askDeleteDepartment,
    cancelDelete,
    submitForm,
    submitNewYear,
    deleteDepartment,
    deleteYear,
  }
}
