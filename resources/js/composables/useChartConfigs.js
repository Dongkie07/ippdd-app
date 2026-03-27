/**
 * composables/useChartConfigs.js
 * ─────────────────────────────────────────────────────────────
 * All Chart.js dataset + options builders for WFP charts.
 * Keeps chart config out of page components so pages stay thin.
 *
 * Usage (Dashboard.vue):
 *   const { barData, barOpts, lineData, lineOpts, donutData, donutOpts }
 *     = useChartConfigs(props, year)
 *
 * Usage (Budget.vue):
 *   const { trendData, trendOpts, fundMixData, fundMixOpts, yoyData, yoyOpts }
 *     = useBudgetCharts(props, year)
 *
 * All data builders are computed() so they auto-update when props/year change.
 */
import { computed } from 'vue'
import { COLORS, FUNDS, CHART_SCALE_DEFAULTS } from '@/constants/wfp'

// ── Shared tooltip formatter ──────────────────────────────────
const pesoLabel = (ctx) => `  ₱${Number(ctx.raw).toLocaleString('en-PH')}`

// ── Dashboard charts ──────────────────────────────────────────

/**
 * Top 10 departments grouped bar chart.
 * @param {import('vue').Ref} propsRef  — component props (reactive)
 * @param {import('vue').Ref<number>} year
 */
export function useBarChart(propsRef, year) {
  const barData = computed(() => {
    const depts = propsRef.deptData?.[year.value] ?? []
    return {
      labels: depts.map(d =>
        d.department.length > 20 ? d.department.slice(0, 19) + '…' : d.department
      ),
      datasets: [{
        label:           'Total Budget',
        data:            depts.map(d => d.budget_total ?? 0),
        backgroundColor: COLORS.navy,
        borderRadius:    4,
        borderSkipped:   false,
      }],
    }
  })

  const barOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend:  { display: false },
      tooltip: { callbacks: { label: pesoLabel } },
    },
    scales: {
      x: { ...CHART_SCALE_DEFAULTS.x, ticks: { ...CHART_SCALE_DEFAULTS.x.ticks, font: { size: 10 } } },
      y: CHART_SCALE_DEFAULTS.y,
    },
  }

  return { barData, barOpts }
}

/**
 * 3-year budget trend line chart (total + Fund 101 + Fund 164).
 * @param {import('vue').Ref} propsRef
 */
export function useLineChart(propsRef) {
  const lineData = computed(() => {
    const ys = propsRef.yearSummary ?? []
    return {
      labels:   ys.map(y => `FY ${y.year}`),
      datasets: [
        {
          label:           'Total Budget',
          data:            ys.map(y => y.total_budget ?? 0),
          borderColor:     COLORS.navy,
          backgroundColor: COLORS.navyLight,
          fill:            true,
          tension:         0.3,
          pointRadius:     6,
          pointBackgroundColor: COLORS.navy,
          borderWidth:     2.5,
        },
        {
          label:           'Fund 101 (GAA)',
          data:            ys.map(y => y.total_101 ?? 0),
          borderColor:     COLORS.gold,
          backgroundColor: 'transparent',
          fill:            false,
          tension:         0.3,
          pointRadius:     4,
          pointBackgroundColor: COLORS.gold,
          borderWidth:     2,
          borderDash:      [5, 4],
        },
        {
          label:           'Fund 164 (Fiduciary)',
          data:            ys.map(y => y.total_164 ?? 0),
          borderColor:     COLORS.green,
          backgroundColor: 'transparent',
          fill:            false,
          tension:         0.3,
          pointRadius:     4,
          pointBackgroundColor: COLORS.green,
          borderWidth:     2,
          borderDash:      [3, 3],
        },
      ],
    }
  })

  const lineOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend:  { labels: { color: COLORS.gray, font: { size: 11 }, boxWidth: 12, padding: 16 } },
      tooltip: { callbacks: { label: pesoLabel } },
    },
    scales: CHART_SCALE_DEFAULTS,
  }

  return { lineData, lineOpts }
}

/**
 * Fund mix donut chart (top 6 departments).
 * @param {import('vue').Ref} propsRef
 * @param {import('vue').Ref<number>} year
 */
export function useDonutChart(propsRef, year) {
  const donutData = computed(() => {
    const top = (propsRef.deptData?.[year.value] ?? []).slice(0, 6)
    return {
      labels:   top.map(d => d.department.length > 22 ? d.department.slice(0, 21) + '…' : d.department),
      datasets: [{
        data:            top.map(d => d.budget_total ?? 0),
        backgroundColor: [COLORS.navy, COLORS.gold, COLORS.green, COLORS.blue, COLORS.darkBlue, '#8E44AD'],
        borderWidth:     2,
        borderColor:     '#fff',
      }],
    }
  })

  const donutOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend:  { position: 'bottom', labels: { color: COLORS.gray, font: { size: 10 }, boxWidth: 10, padding: 10 } },
      tooltip: { callbacks: { label: (ctx) => `  ${ctx.label}: ₱${Number(ctx.raw).toLocaleString('en-PH')}` } },
    },
  }

  return { donutData, donutOpts }
}

// ── Budget breakdown charts ───────────────────────────────────

/**
 * Stacked fund-mix bar chart (Budget.vue fund mix tab).
 * @param {Array} rows  — top 15 dept rows with f101_2026 etc.
 */
export function useFundMixChart(rows) {
  const fundMixData = computed(() => ({
    labels:   rows.value.map(r => r.department.length > 22 ? r.department.slice(0, 21) + '…' : r.department),
    datasets: FUNDS.map(f => ({
      label:           f.shortLabel,
      data:            rows.value.map(r => r[`${f.key}_2026`] ?? 0),
      backgroundColor: f.color,
    })),
  }))

  const fundMixOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend:  { position: 'top', labels: { color: COLORS.gray, font: { size: 11 }, boxWidth: 10, padding: 14 } },
      tooltip: { callbacks: { label: (ctx) => `  ${ctx.dataset.label}: ₱${Number(ctx.raw).toLocaleString('en-PH')}` } },
    },
    scales: {
      x: { stacked: true, ticks: { color: COLORS.grayLight, font: { size: 9 } }, grid: { display: false } },
      y: { stacked: true, ...CHART_SCALE_DEFAULTS.y },
    },
  }

  return { fundMixData, fundMixOpts }
}

/**
 * Grouped YoY bar chart — 3 bars per department (2024/2025/2026).
 * @param {import('vue').Ref<Array>} yoyRows
 */
export function useYoyChart(yoyRows) {
  const yoyData = computed(() => {
    const rows = (yoyRows.value ?? []).filter(r => r.budget_2026).slice(0, 15)
    return {
      labels:   rows.map(r => r.department.length > 20 ? r.department.slice(0, 19) + '…' : r.department),
      datasets: [
        { label: 'FY 2024', data: rows.map(r => r.budget_2024 ?? 0), backgroundColor: 'rgba(13,33,55,0.25)', borderRadius: 3 },
        { label: 'FY 2025', data: rows.map(r => r.budget_2025 ?? 0), backgroundColor: 'rgba(13,33,55,0.55)', borderRadius: 3 },
        { label: 'FY 2026', data: rows.map(r => r.budget_2026 ?? 0), backgroundColor: COLORS.navy,           borderRadius: 3 },
      ],
    }
  })

  const yoyOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend:  { position: 'top', labels: { color: COLORS.gray, font: { size: 11 }, boxWidth: 10, padding: 14 } },
      tooltip: { callbacks: { label: pesoLabel } },
    },
    scales: {
      x: { ticks: { color: COLORS.grayLight, font: { size: 9 } }, grid: { display: false } },
      y: CHART_SCALE_DEFAULTS.y,
    },
  }

  return { yoyData, yoyOpts }
}


// ── Convenience wrapper used by Dashboard.vue ─────────────────
// Bundles bar + line + donut into one call so Dashboard only
// needs a single import:
//   const { barData, barOpts, lineData, lineOpts, donutData, donutOpts }
//     = useChartConfigs(props, year)
export function useChartConfigs(props, year) {
  const { barData,   barOpts   } = useBarChart(props, year)
  const { lineData,  lineOpts  } = useLineChart(props)
  const { donutData, donutOpts } = useDonutChart(props, year)
  return { barData, barOpts, lineData, lineOpts, donutData, donutOpts }
} 