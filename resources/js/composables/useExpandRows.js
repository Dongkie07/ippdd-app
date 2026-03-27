/**
 * composables/useExpandRows.js
 * ─────────────────────────────────────────────────────────────
 * Manage expand/collapse state for tree-style tables.
 * Uses a plain reactive array (not a Set) to avoid Vue 3
 * reactivity tracking issues with Set mutations.
 *
 * Usage:
 *   const { toggleExpand, isExpanded, expandAll, collapseAll } =
 *     useExpandRows()
 *
 * Template:
 *   @click="toggleExpand(row.department)"
 *   :class="{ 'rotate-90': isExpanded(row.department) }"
 *   v-if="isExpanded(row.department)"
 */
import { ref } from 'vue'

export function useExpandRows() {

  // Array of currently-expanded identifiers (dept name strings)
  const expanded = ref([])

  /**
   * Toggle a single row open/closed.
   * @param {string} id  — department name or any unique string key
   */
  const toggleExpand = (id) => {
    const idx = expanded.value.indexOf(id)
    if (idx >= 0) {
      expanded.value.splice(idx, 1)   // collapse
    } else {
      expanded.value.push(id)          // expand
    }
  }

  /**
   * Returns true if the given id is currently expanded.
   * @param {string} id
   * @returns {boolean}
   */
  const isExpanded = (id) => expanded.value.includes(id)

  /**
   * Expand all rows from a provided list of ids.
   * @param {string[]} ids
   */
  const expandAll = (ids) => {
    ids.forEach(id => {
      if (!expanded.value.includes(id)) expanded.value.push(id)
    })
  }

  /**
   * Collapse all rows.
   */
  const collapseAll = () => {
    expanded.value = []
  }

  return { expanded, toggleExpand, isExpanded, expandAll, collapseAll }
}
