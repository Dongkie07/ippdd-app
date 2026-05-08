/**
 * composables/useChartConfigs.js
 * ─────────────────────────────────────────────────────────────────────
 * All Chart.js dataset + options builders for WFP charts.
 * All year references are DYNAMIC — no hardcoded 2024/2025/2026.
 */
import { computed } from 'vue'
import { COLORS, FUNDS, CHART_SCALE_DEFAULTS } from '@/constants/wfp'

// ── Shared tooltip formatter ────────────────────────────────────────
const pesoLabel = (ctx) => `  ₱${Number(ctx.raw).toLocaleString('en-PH')}`

// ── Dashboard charts ────────────────────────────────────────────────

/**
 * Top 10 departments bar chart.
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
 * Budget trend line chart (total + Fund 101 + Fund 164).
 */
export function useLineChart(propsRef) {
  const lineData = computed(() => {
    const ys = propsRef.yearSummary ?? []
    return {
      labels:   ys.map(y => `FY ${y.year}`),
      datasets: [
        {
          label:                'Total Budget',
          data:                 ys.map(y => y.total_budget ?? 0),
          borderColor:          COLORS.navy,
          backgroundColor:      COLORS.navyLight,
          fill:                 true, tension: 0.3, pointRadius: 6,
          pointBackgroundColor: COLORS.navy, borderWidth: 2.5,
        },
        {
          label:                'Fund 101 (GAA)',
          data:                 ys.map(y => y.total_101 ?? 0),
          borderColor:          COLORS.gold,
          backgroundColor:      'transparent',
          fill:                 false, tension: 0.3, pointRadius: 4,
          pointBackgroundColor: COLORS.gold, borderWidth: 2, borderDash: [5, 4],
        },
        {
          label:                'Fund 164 (Fiduciary)',
          data:                 ys.map(y => y.total_164 ?? 0),
          borderColor:          COLORS.green,
          backgroundColor:      'transparent',
          fill:                 false, tension: 0.3, pointRadius: 4,
          pointBackgroundColor: COLORS.green, borderWidth: 2, borderDash: [3, 3],
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

// ── Budget breakdown charts ─────────────────────────────────────────

/**
 * Stacked fund-mix bar chart (Budget.vue fund mix tab).
 *
 * FIX: Instead of filtering rows by latest year only, we show ALL rows
 * that have a budget in ANY year, sorted by max budget across all years.
 * This ensures departments with older data still appear.
 *
 * @param {Ref<Array>} rows        — yoyRows (all departments, all years)
 * @param {Ref<number>} latestYear — the most recent year (dynamic)
 */
export function useFundMixChart(rows, latestYear) {
  const fundMixData = computed(() => {
    const yr = latestYear?.value ?? null
    if (!yr) return { labels: [], datasets: [] }

    // ── Pick top 15 by max budget across ANY year ────────────────
    // This way departments without FY 2028 data still show up
    const sorted = [...(rows.value ?? [])]
      .filter(r => {
        // Has budget in at least one year
        return Object.keys(r).some(k => k.startsWith('budget_') && r[k] > 0)
      })
      .sort((a, b) => {
        const maxA = Math.max(...Object.keys(a).filter(k => k.startsWith('budget_')).map(k => a[k] ?? 0))
        const maxB = Math.max(...Object.keys(b).filter(k => k.startsWith('budget_')).map(k => b[k] ?? 0))
        return maxB - maxA
      })
      .slice(0, 15)

    return {
      labels: sorted.map(r =>
        r.department.length > 22 ? r.department.slice(0, 21) + '…' : r.department
      ),
      datasets: FUNDS.map(f => ({
        label:           f.shortLabel,
        // Use the latest year's fund data; fall back to 0 if not available
        data:            sorted.map(r => r[`${f.key}_${yr}`] ?? 0),
        backgroundColor: f.color,
      })),
    }
  })

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
 * Grouped YoY bar chart — N bars per department (one per year in DB).
 *
 * FIX: Instead of filtering by latest year only, we take top 15 by
 * max budget across ALL years. Departments that don't have the latest
 * year's data still appear with their other years' bars.
 *
 * @param {Ref<Array>} yoyRows
 * @param {Ref<Array>} years    — dynamic year list e.g. [2024, 2025, 2026, 2027, 2028]
 */
export function useYoyChart(yoyRows, years) {
  const yoyData = computed(() => {
    const yrs = years?.value ?? []
    if (!yrs.length) return { labels: [], datasets: [] }

    // ── Top 15 by max budget across ALL years ────────────────────
    const rows = [...(yoyRows.value ?? [])]
      .filter(r => yrs.some(yr => (r[`budget_${yr}`] ?? 0) > 0))
      .sort((a, b) => {
        const maxA = Math.max(...yrs.map(yr => a[`budget_${yr}`] ?? 0))
        const maxB = Math.max(...yrs.map(yr => b[`budget_${yr}`] ?? 0))
        return maxB - maxA
      })
      .slice(0, 15)

    const opacity = ['0.15', '0.30', '0.50', '0.70', '0.85', '1.00']

    return {
      labels: rows.map(r =>
        r.department.length > 20 ? r.department.slice(0, 19) + '…' : r.department
      ),
      datasets: yrs.map((yr, i) => ({
        label:           `FY ${yr}`,
        data:            rows.map(r => r[`budget_${yr}`] ?? 0),
        backgroundColor: i === yrs.length - 1
          ? COLORS.navy
          : `rgba(13,33,55,${opacity[Math.min(i, opacity.length - 2)]})`,
        borderRadius: 3,
      })),
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

// ── Convenience wrapper for Dashboard.vue ──────────────────────────
export function useChartConfigs(props, year) {
  const { barData,   barOpts   } = useBarChart(props, year)
  const { lineData,  lineOpts  } = useLineChart(props)
  const { donutData, donutOpts } = useDonutChart(props, year)
  return { barData, barOpts, lineData, lineOpts, donutData, donutOpts }
}