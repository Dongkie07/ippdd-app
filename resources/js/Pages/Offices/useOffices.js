import { computed, ref, watchEffect } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const FEEDBACK_TIMEOUT_MS = 4000
const COMPLETE_HEALTH_SCORE = 100
const EMPTY_VALUE = '—'

const blankOfficeForm = () => ({
  id: null,
  current_name: '',
  acronym: '',
  status: 'Active',
  effective_from_year: new Date().getFullYear(),
})

const blankHistoryForm = () => ({
  name: '',
  acronym: '',
  effective_from_year: new Date().getFullYear() - 1,
  effective_to_year: '',
})

const collectErrors = (errors) => Object.values(errors ?? {}).flat().join(' ')

const normalizeText = (value) => String(value ?? '').toLowerCase().trim()

const sortHistoriesByYear = (histories = []) => [...histories].sort((firstHistory, secondHistory) => {
  const firstYear = firstHistory.effective_from_year ?? 0
  const secondYear = secondHistory.effective_from_year ?? 0

  return firstYear - secondYear
})

const getHistoryPeriod = (history) => {
  const startYear = history.effective_from_year ?? EMPTY_VALUE
  const endYear = history.effective_to_year ?? 'Present'

  return `${startYear}–${endYear}`
}

const calculateCompletionRate = (completeCount, totalCount) => {
  if (!totalCount) return 0

  return Math.round((completeCount / totalCount) * COMPLETE_HEALTH_SCORE)
}

export function useOffices(props) {
  const page = usePage()
  const query = ref('')
  const saving = ref(false)
  const feedback = ref(null)
  const selectedOfficeId = ref(props.offices?.[0]?.id ?? null)
  const officeForm = ref(blankOfficeForm())
  const historyForm = ref(blankHistoryForm())
  const formMode = ref('add')

  const offices = computed(() => props.offices ?? [])
  const selectedOffice = computed(() => offices.value.find((office) => office.id === selectedOfficeId.value) ?? null)
  const hasSearchQuery = computed(() => normalizeText(query.value).length > 0)

  const filteredOffices = computed(() => {
    const needle = normalizeText(query.value)
    if (!needle) return offices.value

    return offices.value.filter((office) => [
      office.current_name,
      office.acronym,
      office.office_key,
      office.status,
      ...(office.histories ?? []).flatMap((history) => [history.name, history.acronym, getHistoryPeriod(history)]),
    ].filter(Boolean).some((value) => normalizeText(value).includes(needle)))
  })

  const officeStats = computed(() => ({
    total: offices.value.length,
    active: offices.value.filter((office) => office.status === 'Active').length,
    inactive: offices.value.filter((office) => office.status === 'Inactive').length,
    aliases: offices.value.reduce((sum, office) => sum + (office.histories?.length ?? 0), 0),
  }))

  const registryHealth = computed(() => {
    const totalOffices = offices.value.length
    const officesWithHistory = offices.value.filter((office) => (office.histories?.length ?? 0) > 0).length
    const linkedRows = Math.max(0, totalOffices - (props.unlinkedSubmissionCount ? 1 : 0))

    return {
      historyRate: calculateCompletionRate(officesWithHistory, totalOffices),
      activeRate: calculateCompletionRate(officeStats.value.active, totalOffices),
      syncRate: props.unlinkedSubmissionCount > 0 ? 72 : COMPLETE_HEALTH_SCORE,
      linkedRows,
    }
  })

  const selectedTimeline = computed(() => {
    if (!selectedOffice.value) return []

    return sortHistoriesByYear(selectedOffice.value.histories).map((history) => ({
      id: history.id,
      name: history.name,
      acronym: history.acronym,
      period: getHistoryPeriod(history),
      isCurrent: !history.effective_to_year,
      original: history,
    }))
  })

  const selectedPreview = computed(() => {
    const office = selectedOffice.value

    if (!office) {
      return {
        latestLabel: EMPTY_VALUE,
        byYearLabel: EMPTY_VALUE,
        identityKey: EMPTY_VALUE,
      }
    }

    const latestHistory = selectedTimeline.value.find((history) => history.isCurrent)
    const oldestHistory = selectedTimeline.value[0]

    return {
      latestLabel: office.current_name,
      byYearLabel: oldestHistory?.name ?? office.current_name,
      identityKey: office.office_key ?? EMPTY_VALUE,
      latestPeriod: latestHistory?.period ?? 'Current label',
      historicalPeriod: oldestHistory?.period ?? 'No yearly name yet',
    }
  })

  const toast = (type, message) => {
    feedback.value = { type, message }
    window.setTimeout(() => { feedback.value = null }, FEEDBACK_TIMEOUT_MS)
  }

  watchEffect(() => {
    const flash = page.props.flash
    if (flash?.message) toast('success', flash.message)
  })

  const submit = (method, url, data) => new Promise((resolve) => {
    saving.value = true
    router[method](url, data, {
      preserveScroll: true,
      onSuccess: () => resolve({ success: true }),
      onError: (errors) => resolve({ success: false, errors }),
      onFinish: () => { saving.value = false },
    })
  })

  const openAddOffice = () => {
    formMode.value = 'add'
    officeForm.value = blankOfficeForm()
  }

  const openEditOffice = (office) => {
    formMode.value = 'edit'
    selectedOfficeId.value = office.id
    officeForm.value = {
      id: office.id,
      current_name: office.current_name,
      acronym: office.acronym ?? '',
      status: office.status ?? 'Active',
      effective_from_year: new Date().getFullYear(),
    }
  }

  const selectOffice = (office) => {
    selectedOfficeId.value = office.id
    openEditOffice(office)
  }

  const clearSearch = () => {
    query.value = ''
  }

  const saveOffice = async () => {
    const isEdit = formMode.value === 'edit' && officeForm.value.id
    const url = isEdit ? `/offices/${officeForm.value.id}` : '/offices'
    const result = await submit(isEdit ? 'put' : 'post', url, officeForm.value)

    if (result.success) {
      toast('success', isEdit ? 'Office updated.' : 'Office added.')
      return
    }

    toast('error', collectErrors(result.errors) || 'Office could not be saved.')
  }

  const saveHistory = async () => {
    if (!selectedOffice.value) return

    const result = await submit('post', `/offices/${selectedOffice.value.id}/histories`, historyForm.value)

    if (result.success) {
      toast('success', 'Historical name saved.')
      historyForm.value = blankHistoryForm()
      return
    }

    toast('error', collectErrors(result.errors) || 'Historical name could not be saved.')
  }

  const removeHistory = async (history) => {
    const result = await submit('delete', `/office-histories/${history.id}`)

    if (result.success) {
      toast('success', 'Historical name removed.')
      return
    }

    toast('error', collectErrors(result.errors) || 'Historical name could not be removed.')
  }

  const syncOffices = async () => {
    const result = await submit('post', '/offices/sync')

    if (!result.success) toast('error', 'Could not sync the office registry.')
  }

  return {
    query,
    saving,
    feedback,
    officeForm,
    historyForm,
    formMode,
    offices,
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
    openEditOffice,
    selectOffice,
    saveOffice,
    saveHistory,
    removeHistory,
    syncOffices,
  }
}
