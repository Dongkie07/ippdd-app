/**
 * composables/useFormatters.js
 * ─────────────────────────────────────────────────────────────
 * All currency / percentage formatting helpers for WFP pages.
 *
 * Usage:
 *   const { php, phpM, phpK, pct, fundPct } = useFormatters()
 */

export function useFormatters() {

  /**
   * Full peso amount  →  ₱1,234,567.89
   * Returns '—' for null / undefined
   */
  const php = (v) => {
    if (v == null) return '—'
    return '₱' + Number(v).toLocaleString('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  }

  /**
   * Amount in millions  →  ₱12.34M
   * Returns '—' for null / undefined
   */
  const phpM = (v) => {
    if (v == null) return '—'
    return '₱' + (Number(v) / 1_000_000).toFixed(2) + 'M'
  }

  /**
   * Amount in thousands  →  ₱123K
   */
  const phpK = (v) => {
    if (v == null) return '—'
    return '₱' + (Number(v) / 1_000).toFixed(1) + 'K'
  }

  /**
   * Year-over-year percentage change  →  +12.3%  or  -5.1%
   * Returns '—' for null / undefined
   */
  const pct = (v) => {
    if (v == null) return '—'
    const sign = v > 0 ? '+' : ''
    return `${sign}${Number(v).toFixed(1)}%`
  }

  /**
   * Fund share as % of total
   * @param {number} fundAmount
   * @param {number} totalBudget
   * @returns {string}  e.g. "42.5%"
   */
  const fundPct = (fundAmount, totalBudget) => {
    if (!totalBudget || totalBudget === 0) return '0%'
    return ((fundAmount / totalBudget) * 100).toFixed(1) + '%'
  }

  /**
   * Budget field accessor — handles both old and new DB field names.
   * Old: budget / fund_101 / fund_164 etc.
   * New: budget_total / budget_fund_101 etc.
   */
  const bget  = (d) => d?.budget_total    ?? d?.budget   ?? 0
  const f101  = (d) => d?.budget_fund_101 ?? d?.fund_101 ?? 0
  const f164  = (d) => d?.budget_fund_164 ?? d?.fund_164 ?? 0
  const f161  = (d) => d?.budget_fund_161 ?? d?.fund_161 ?? 0
  const f163  = (d) => d?.budget_fund_163 ?? d?.fund_163 ?? 0

  return { php, phpM, phpK, pct, fundPct, bget, f101, f164, f161, f163 }
}
