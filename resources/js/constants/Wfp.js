/**
 * constants/wfp.js
 * ─────────────────────────────────────────────────────────────
 * Single source of truth for WFP labels, fiscal-year defaults,
 * and the DNSC-inspired IPPDD green palette.
 */

// ── Brand colors derived from the DNSC green system screenshot ──
export const COLORS = {
  navy:       '#064E3B', // deep institutional green
  navyLight:  'rgba(6, 78, 59, 0.08)',
  green:      '#168A4A',
  emerald:    '#0F9F5A',
  mint:       '#53D28C',
  teal:       '#0F766E',
  gold:       '#D6B74A',
  darkBlue:   '#0F766E', // kept for backwards compatibility with older imports
  blue:       '#059669', // kept for backwards compatibility with older imports
  gray:       '#64746B',
  grayLight:  '#8FA79B',
  gridLine:   '#E6F2EA',
  surface:    '#F4F8F5',
  surfaceSoft:'#ECFDF3',
  border:     '#DDEDE3',
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
    color:      COLORS.teal,
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
  x: {
    ticks: { color: COLORS.grayLight, font: { family: 'Inter, ui-sans-serif, system-ui' } },
    grid:  { color: COLORS.gridLine },
  },
  y: {
    ticks: {
      color: COLORS.grayLight,
      font: { family: 'Inter, ui-sans-serif, system-ui' },
      callback: (v) => '₱' + (v / 1e6).toFixed(0) + 'M',
    },
    grid:  { color: COLORS.gridLine },
  },
}
