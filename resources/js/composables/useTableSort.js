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
  const applySortTo = (list) => {
    const dir = sortDir.value === 'asc' ? 1 : -1
    return [...list].sort((a, b) => {
      const av = a[sortBy.value] ?? (typeof a[sortBy.value] === 'string' ? '' : 0)
      const bv = b[sortBy.value] ?? (typeof b[sortBy.value] === 'string' ? '' : 0)
      if (av > bv) return dir
      if (av < bv) return -dir
      return 0
    })
  }

  return { sortBy, sortDir, setSort, sortIcon, applySortTo }
}
