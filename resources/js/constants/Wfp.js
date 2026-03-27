/**
 * constants/wfp.js
 * ─────────────────────────────────────────────────────────────
 * Single source of truth for all WFP-related constants.
 * Import from here — never hard-code colors or fund names inline.
 *
 * Usage:
 *   import { FUNDS, COLORS, FISCAL_YEARS } from '@/constants/wfp'
 */

// ── Brand colors ──────────────────────────────────────────────
export const COLORS = {
  navy:      '#0D2137',
  navyLight: 'rgba(13, 33, 55, 0.07)',
  gold:      '#C9A84C',
  green:     '#1E8449',
  blue:      '#2E86C1',
  darkBlue:  '#1A5276',
  gray:      '#6B7280',
  grayLight: '#9CA3AF',
  gridLine:  '#F3F4F6',
}

// ── Fund definitions ──────────────────────────────────────────
// Each entry: { key, dbField, label, shortLabel, color }
export const FUNDS = [
  {
    key:        'f101',
    dbField:    'budget_fund_101',
    label:      'Fund 101 — GAA (National Expenditure Program)',
    shortLabel: 'Fund 101 — GAA',
    color:      COLORS.navy,
  },
  {
    key:        'f164',
    dbField:    'budget_fund_164',
    label:      'Fund 164 — Fiduciary (Trust / Special Funds)',
    shortLabel: 'Fund 164 — Fiduciary',
    color:      COLORS.gold,
  },
  {
    key:        'f161',
    dbField:    'budget_fund_161',
    label:      'Fund 161 — Internally Generated Funds',
    shortLabel: 'Fund 161',
    color:      COLORS.green,
  },
  {
    key:        'f163',
    dbField:    'budget_fund_163',
    label:      'Fund 163 — Retained Income / Business Income',
    shortLabel: 'Fund 163',
    color:      COLORS.blue,
  },
]

// Quick lookups
export const FUND_BY_KEY   = Object.fromEntries(FUNDS.map(f => [f.key,     f]))
export const FUND_BY_FIELD = Object.fromEntries(FUNDS.map(f => [f.dbField, f]))

// ── Fiscal years ──────────────────────────────────────────────
export const FISCAL_YEARS = [2024, 2025, 2026]
export const LATEST_YEAR  = 2026

// ── Default chart scale options (reuse in useChartConfigs) ────
export const CHART_SCALE_DEFAULTS = {
  x: { ticks: { color: COLORS.grayLight }, grid: { color: COLORS.gridLine } },
  y: {
    ticks: { color: COLORS.grayLight, callback: (v) => '₱' + (v / 1e6).toFixed(0) + 'M' },
    grid:  { color: COLORS.gridLine },
  },
}