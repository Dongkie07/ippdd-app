/**
 * composables/useChartConfigs.js
 * ─────────────────────────────────────────────────────────────────────
 * All Chart.js dataset + options builders for WFP charts.
 * All year references are DYNAMIC — no hardcoded 2024/2025/2026.
 */
import { computed } from 'vue'
import { COLORS, FUNDS, CHART_SCALE_DEFAULTS } from '@/constants/Wfp'

// ── Shared tooltip formatter ────────────────────────────────────────
const pesoLabel = (ctx) => `  ₱${Number(ctx.raw).toLocaleString('en-PH')}`

// DNSC-inspired palette. Used by BOTH the Top 10 bar chart and donut chart.
export const OFFICE_CHART_COLORS = [
  '#064E3B', // deep green
  '#168A4A', // institutional green
  '#53D28C', // mint accent
  '#D6B74A', // gold seal accent
  '#0F766E', // teal green
  '#2E7D32', // classic green
  '#86C232', // fresh green
  '#22543D', // forest
  '#9A7B16', // antique gold
  '#64746B', // soft slate
]

const commonChartPlugins = {
  tooltip: {
    backgroundColor: '#022C22',
    titleColor: '#DDFBE8',
    bodyColor: '#FFFFFF',
    borderColor: 'rgba(83,210,140,.28)',
    borderWidth: 1,
    padding: 12,
    displayColors: true,
  },
}

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
        backgroundColor: depts.map((_, index) => OFFICE_CHART_COLORS[index % OFFICE_CHART_COLORS.length]),
        hoverBackgroundColor: depts.map((_, index) => OFFICE_CHART_COLORS[index % OFFICE_CHART_COLORS.length]),
        borderRadius:    10,
        borderSkipped:   false,
        barThickness:    22,
      }],
    }
  })

  const barOpts = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 650, easing: 'easeOutQuart' },
    plugins: {
      legend:  { display: false },
      tooltip: { ...commonChartPlugins.tooltip, callbacks: { label: pesoLabel } },
    },
    scales: {
      x: {
        ...CHART_SCALE_DEFAULTS.x,
        ticks: { ...CHART_SCALE_DEFAULTS.x.ticks, font: { size: 10, weight: 700 } },
        grid: { display: false },
      },
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
          backgroundColor:      'rgba(6,78,59,0.10)',
          fill:                 true,
          tension:              0.35,
          pointRadius:          6,
          pointHoverRadius:     8,
          pointBackgroundColor: COLORS.navy,
          pointBorderColor:     '#fff',
          pointBorderWidth:     2,
          borderWidth:          3,
        },
        {
          label:                'Fund 101 (GAA)',
          data:                 ys.map(y => y.total_101 ?? 0),
          borderColor:          COLORS.gold,
          backgroundColor:      'transparent',
          fill:                 false,
          tension:              0.35,
          pointRadius:          4,
          pointBackgroundColor: COLORS.gold,
          borderWidth:          2,
          borderDash:           [5, 4],
        },
        {
          label:                'Fund 164 (Fiduciary)',
          data:                 ys.map(y => y.total_164 ?? 0),
          borderColor:          COLORS.green,
          backgroundColor:      'transparent',
          fill:                 false,
          tension:              0.35,
          pointRadius:          4,
          pointBackgroundColor: COLORS.green,
          borderWidth:          2,
          borderDash:           [3, 3],
        },
                {
          label: 'Fund 161',
          data: ys.map(y => y.total_161 ?? 0),
          borderColor: COLORS.mint,
          backgroundColor: 'transparent',
          fill: false,
          tension: 0.35,
          pointRadius: 4,
          pointBackgroundColor: COLORS.mint,
          borderWidth: 2,
          borderDash: [6, 3],
        },
        {
          label: 'Fund 163',
          data: ys.map(y => y.total_163 ?? 0),
          borderColor: COLORS.teal,
          backgroundColor: 'transparent',
          fill: false,
          tension: 0.35,
          pointRadius: 4,
          pointBackgroundColor: COLORS.teal,
          borderWidth: 2,
          borderDash: [2, 4],
        },
      ],
    }
  })

  const lineOpts = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 700, easing: 'easeOutQuart' },
    plugins: {
      legend:  {
        labels: {
          color: COLORS.gray,
          font: { size: 11, weight: 700 },
          boxWidth: 12,
          padding: 16,
          usePointStyle: true,
        },
      },
      tooltip: { ...commonChartPlugins.tooltip, callbacks: { label: pesoLabel } },
    },
    scales: CHART_SCALE_DEFAULTS,
  }

  return { lineData, lineOpts }
}

/**
 * Top 10 office share donut chart.
 */
export function useDonutChart(propsRef, year) {
  const donutData = computed(() => {
    const top = (propsRef.deptData?.[year.value] ?? []).slice(0, 10)
    return {
      labels:   top.map(d => d.department.length > 22 ? d.department.slice(0, 21) + '…' : d.department),
      datasets: [{
        data:            top.map(d => d.budget_total ?? 0),
        backgroundColor: top.map((_, index) => OFFICE_CHART_COLORS[index % OFFICE_CHART_COLORS.length]),
        hoverBackgroundColor: top.map((_, index) => OFFICE_CHART_COLORS[index % OFFICE_CHART_COLORS.length]),
        borderWidth:     3,
        borderColor:     '#fff',
        hoverOffset:     8,
      }],
    }
  })

  const donutOpts = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '66%',
    animation: { animateRotate: true, duration: 700, easing: 'easeOutQuart' },
    plugins: {
      legend:  {
        position: 'bottom',
        labels: {
          color: COLORS.gray,
          font: { size: 10, weight: 700 },
          boxWidth: 10,
          padding: 12,
          usePointStyle: true,
        },
      },
      tooltip: { ...commonChartPlugins.tooltip, callbacks: { label: (ctx) => `  ${ctx.label}: ₱${Number(ctx.raw).toLocaleString('en-PH')}` } },
    },
  }

  return { donutData, donutOpts }
}

// ── Budget breakdown charts ─────────────────────────────────────────

/**
 * Stacked fund-mix bar chart (Budget.vue fund mix tab).
 */
export function useFundMixChart(rows, years) {
  const fundMixData = computed(() => {
    const yrs = years?.value ?? []
    if (!yrs.length) return { labels: [], datasets: [] }

    const num = value => Number(value ?? 0) || 0

    const topRows = [...(rows.value ?? [])]
      .filter(row =>
        yrs.some(yr =>
          FUNDS.some(fund => num(row[`${fund.key}_${yr}`]) > 0) ||
          num(row[`budget_${yr}`]) > 0
        )
      )
      .sort((a, b) => {
        const maxA = Math.max(...yrs.map(yr => num(a[`budget_${yr}`])))
        const maxB = Math.max(...yrs.map(yr => num(b[`budget_${yr}`])))
        return maxB - maxA
      })
      .slice(0, 15)

    const datasets = []

    yrs.forEach(yr => {
      FUNDS.forEach(fund => {
        datasets.push({
          label: `${fund.shortLabel} · FY ${yr}`,
          data: topRows.map(row => num(row[`${fund.key}_${yr}`])),
          backgroundColor: fund.color,
          stack: `FY ${yr}`,
          borderRadius: 6,
          borderSkipped: false,
        })
      })
    })

    return {
      labels: topRows.map(row => {
        const name = row.department ?? 'Unknown Department'
        return name.length > 20 ? name.slice(0, 19) + '…' : name
      }),
      datasets,
    }
  })

  const fundMixOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
        labels: {
          color: COLORS.gray,
          font: { size: 10, weight: 700 },
          boxWidth: 10,
          padding: 10,
          usePointStyle: true,
        },
      },
      tooltip: { ...commonChartPlugins.tooltip, callbacks: { label: ctx => `  ${ctx.dataset.label}: ₱${Number(ctx.raw).toLocaleString('en-PH')}` } },
    },
    scales: {
      x: {
        stacked: true,
        ticks: { color: COLORS.grayLight, font: { size: 9, weight: 700 } },
        grid: { display: false },
      },
      y: {
        stacked: true,
        ...CHART_SCALE_DEFAULTS.y,
      },
    },
  }

  return { fundMixData, fundMixOpts }
}

/**
 * Grouped YoY bar chart — N bars per department (one per year in DB).
 */
export function useYoyChart(yoyRows, years) {
  const yoyData = computed(() => {
    const yrs = years?.value ?? []
    if (!yrs.length) return { labels: [], datasets: [] }

    const rows = [...(yoyRows.value ?? [])]
      .filter(r => yrs.some(yr => (r[`budget_${yr}`] ?? 0) > 0))
      .sort((a, b) => {
        const maxA = Math.max(...yrs.map(yr => a[`budget_${yr}`] ?? 0))
        const maxB = Math.max(...yrs.map(yr => b[`budget_${yr}`] ?? 0))
        return maxB - maxA
      })
      .slice(0, 15)

    return {
      labels: rows.map(r =>
        r.department.length > 20 ? r.department.slice(0, 19) + '…' : r.department
      ),
      datasets: yrs.map((yr, i) => ({
        label:           `FY ${yr}`,
        data:            rows.map(r => r[`budget_${yr}`] ?? 0),
        backgroundColor: OFFICE_CHART_COLORS[i % OFFICE_CHART_COLORS.length],
        borderRadius:    8,
        borderSkipped:   false,
      })),
    }
  })

  const yoyOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend:  { position: 'top', labels: { color: COLORS.gray, font: { size: 11, weight: 700 }, boxWidth: 10, padding: 14, usePointStyle: true } },
      tooltip: { ...commonChartPlugins.tooltip, callbacks: { label: pesoLabel } },
    },
    scales: {
      x: { ticks: { color: COLORS.grayLight, font: { size: 9, weight: 700 } }, grid: { display: false } },
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
