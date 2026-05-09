/**
 * useBudgetBreakdown.js
 * Data shaping for the Department Breakdown page.
 * Put chart/table calculations here so Budget.vue stays readable.
 */
import { computed, ref } from 'vue'
import { useYoyChart } from '@/composables/useChartConfigs'
import { useExpandRows } from '@/composables/useExpandRows'

export const BUDGET_TABS = [
  { id: 'ranking', label: 'Budget Ranking' },
  { id: 'fundmix', label: 'Fund Mix' },
  { id: 'yoy', label: 'Year Comparison' },
]

const changeKey = (fromYear, toYear) => `chg_${String(fromYear).slice(2)}_${String(toYear).slice(2)}`

export function useBudgetBreakdown(props) {
  const activeTab = ref('ranking')
  const search = ref('')

  const yearsRef = computed(() => props.years ?? [])
  const latestYearRef = computed(() => yearsRef.value.at(-1) ?? null)
  const previousYearRef = computed(() => yearsRef.value.at(-2) ?? null)
  const yearTotalsRef = computed(() => props.yearTotals ?? {})
  const yoyRowsRef = computed(() => props.yoyRows ?? [])

  const {
    toggleExpand,
    isExpanded,
    expandAll: expandAllRows,
    collapseAll,
    expanded: expandedRows,
  } = useExpandRows()

  const yoyChangeKey = computed(() =>
    previousYearRef.value && latestYearRef.value
      ? changeKey(previousYearRef.value, latestYearRef.value)
      : null,
  )

  const { yoyData: yoyChartData, yoyOpts: yoyChartOpts } = useYoyChart(yoyRowsRef, yearsRef)

  const findDepartmentInYear = (departmentName, year) => {
    const normalized = departmentName.toUpperCase()
    return (props.allByYear?.[year] ?? []).find((row) => row.department.toUpperCase() === normalized) ?? null
  }

  const enrichParent = (row) => {
    const years = yearsRef.value
    const latestYear = latestYearRef.value
    const previousYear = previousYearRef.value
    const yearFields = {}

    years.forEach((year) => {
      const yearRow = findDepartmentInYear(row.department, year)
      yearFields[`budget_${year}`] = yearRow?.budget_total ?? row[`budget_${year}`] ?? null
      yearFields[`own_${year}`] = yearRow?.own_budget ?? row[`budget_${year}`] ?? 0
      yearFields[`f101_${year}`] = yearRow?.budget_fund_101 ?? row[`f101_${year}`] ?? 0
      yearFields[`f164_${year}`] = yearRow?.budget_fund_164 ?? row[`f164_${year}`] ?? 0
      yearFields[`f161_${year}`] = yearRow?.budget_fund_161 ?? row[`f161_${year}`] ?? 0
      yearFields[`f163_${year}`] = yearRow?.budget_fund_163 ?? row[`f163_${year}`] ?? 0
    })

    let children = []
    for (let i = years.length - 1; i >= 0; i -= 1) {
      const yearRow = findDepartmentInYear(row.department, years[i])
      if (yearRow?.children?.length) {
        children = yearRow.children
        break
      }
    }

    const latestBudget = yearFields[`budget_${latestYear}`]
    const previousBudget = previousYear ? yearFields[`budget_${previousYear}`] : null
    const latestChange = previousBudget && latestBudget
      ? Math.round(((latestBudget - previousBudget) / previousBudget) * 1000) / 10
      : null

    return {
      ...row,
      ...yearFields,
      _isChild: false,
      _hasChildren: children.length > 0,
      _childCount: children.length,
      ...(previousYear && latestYear ? { [changeKey(previousYear, latestYear)]: latestChange } : {}),
    }
  }

  const parentRows = computed(() => {
    const query = search.value.toLowerCase()

    return yoyRowsRef.value
      .filter((row) => !query || row.department.toLowerCase().includes(query))
      .map(enrichParent)
  })

  const filteredTree = computed(() => {
    const years = yearsRef.value
    const latestYear = latestYearRef.value
    const previousYear = previousYearRef.value
    const query = search.value.toLowerCase()
    const result = []

    parentRows.value.forEach((parent) => {
      result.push(parent)

      if (!parent._hasChildren) return

      const allChildren = new Map()

      years.forEach((year) => {
        const yearRow = findDepartmentInYear(parent.department, year)

        ;(yearRow?.children ?? []).forEach((child) => {
          const key = child.department.toUpperCase()
          const existing = allChildren.get(key) ?? {
            department: child.department,
            sheet_code: child.sheet_code ?? '',
          }

          existing[`budget_${year}`] = child.budget_total ?? null
          existing[`f101_${year}`] = child.budget_fund_101 ?? 0
          existing[`f164_${year}`] = child.budget_fund_164 ?? 0
          existing[`f161_${year}`] = child.budget_fund_161 ?? 0
          existing[`f163_${year}`] = child.budget_fund_163 ?? 0

          if (year === latestYear) existing.department = child.department
          allChildren.set(key, existing)
        })
      })

      allChildren.forEach((child) => {
        const latestBudget = child[`budget_${latestYear}`]
        const previousBudget = previousYear ? child[`budget_${previousYear}`] : null
        const latestChange = previousBudget && latestBudget
          ? Math.round(((latestBudget - previousBudget) / previousBudget) * 1000) / 10
          : null

        if (!query || child.department.toLowerCase().includes(query)) {
          result.push({
            ...child,
            ...(previousYear && latestYear ? { [changeKey(previousYear, latestYear)]: latestChange } : {}),
            _isChild: true,
            _parentName: parent.department,
            _hasChildren: false,
            _childCount: 0,
          })
        }
      })
    })

    return result
  })

  const bestYearFor = (row) => {
    const yearsWithBudget = Object.keys(row)
      .filter((key) => key.startsWith('budget_') && (row[key] ?? 0) > 0)
      .map((key) => Number(key.replace('budget_', '')))
      .sort((a, b) => b - a)

    return yearsWithBudget[0] ?? latestYearRef.value
  }

  const fundRows = computed(() =>
    yoyRowsRef.value
      .filter((row) => Object.keys(row).some((key) => key.startsWith('budget_') && row[key] > 0))
      .map((row) => ({ ...row, _bestYear: bestYearFor(row) }))
      .sort((a, b) => (b[`budget_${b._bestYear}`] ?? 0) - (a[`budget_${a._bestYear}`] ?? 0)),
  )

  const expandAll = () => {
    expandAllRows(parentRows.value.filter((row) => row._hasChildren).map((row) => row.department))
  }

  const trendColor = (value) => {
    if (value == null) return 'text-gray-300'
    if (value > 0) return 'text-emerald-600'
    if (value < 0) return 'text-red-500'
    return 'text-gray-400'
  }

  const trendBg = (value) => {
    if (value == null) return ''
    if (value > 0) return 'bg-emerald-50 border-emerald-100'
    if (value < 0) return 'bg-red-50 border-red-100'
    return 'bg-gray-50 border-gray-100'
  }

  return {
    activeTab,
    search,
    tabs: BUDGET_TABS,
    yearsRef,
    latestYearRef,
    previousYearRef,
    yearTotalsRef,
    yoyRowsRef,
    yoyChangeKey,
    yoyChartData,
    yoyChartOpts,
    parentRows,
    filteredTree,
    fundRows,
    expandedRows,
    toggleExpand,
    isExpanded,
    expandAll,
    collapseAll,
    trendColor,
    trendBg,
  }
}
