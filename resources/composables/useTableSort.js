/**
 * composables/useTableSort.js
 * ─────────────────────────────────────────────────────────────
 * Reusable sort state + helpers for any data table.
 *
 * Usage:
 *   const { sortBy, sortDir, setSort, sortIcon, applySortTo } =
 *     useTableSort('budget_total', 'desc')
 *
 * Then in template:
 *   <button @click="setSort('department')">
 *     Department {{ sortIcon('department') }}
 *   </button>
 *
 *   <tr v-for="row in applySortTo(filteredList)" ...>
 */
import { ref } from 'vue'

export function useTableSort(defaultField = 'budget_total', defaultDir = 'desc') {

  const sortBy  = ref(defaultField)
  const sortDir = ref(defaultDir)   // 'asc' | 'desc'

  /**
   * Toggle direction if same field, else switch to new field (desc first).
   */
  const setSort = (field) => {
    if (sortBy.value === field) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortBy.value  = field
      sortDir.value = 'desc'
    }
  }

  /**
   * Returns ↑ or ↓ for the active sort column, '' for others.
   * Use in column headers to show current sort direction.
   */
  const sortIcon = (field) => {
    if (sortBy.value !== field) return ''
    return sortDir.value === 'asc' ? ' ↑' : ' ↓'
  }

  /**
   * Sorts a copy of the array by the active sortBy / sortDir.
   * Does NOT mutate the original array.
   * Handles null/undefined values gracefully (treated as 0 / '').
   *
   * @param {Array} list
   * @returns {Array}
   */
  const normalizeSortValue = (value) => {
    if (value === null || value === undefined || value === '') return 0
    if (typeof value === 'number') return Number.isFinite(value) ? value : 0

    const asText = String(value).trim()
    const asNumber = Number(asText.replace(/[₱,\s]/g, ''))

    return Number.isFinite(asNumber) && asText !== ''
      ? asNumber
      : asText.toLowerCase()
  }

  const applySortTo = (list) => {
    const dir = sortDir.value === 'asc' ? 1 : -1

    return [...list].sort((a, b) => {
      const av = normalizeSortValue(a?.[sortBy.value])
      const bv = normalizeSortValue(b?.[sortBy.value])

      if (typeof av === 'number' && typeof bv === 'number') {
        return (av - bv) * dir
      }

      return String(av).localeCompare(String(bv), undefined, {
        numeric: true,
        sensitivity: 'base',
      }) * dir
    })
  }

  return { sortBy, sortDir, setSort, sortIcon, applySortTo }
}
