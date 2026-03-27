<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function index()
    {
        $table = 'wfp_submissions';
        $years = [2024, 2025, 2026];

        // ── 1. All departments per year (combined parent+children) ──
        $allByYear = [];
        foreach ($years as $yr) {
            $rawRows = DB::table($table)->where('year', $yr)->get();

            // For each top-level dept, combine own budget + children budgets
            $parents = $rawRows->filter(fn($r) => !$r->parent_dept);
            $combined = $parents->map(function($p) use ($rawRows) {
                $children = $rawRows->filter(fn($r) => $r->parent_dept === $p->department);
                return [
                    'id'              => $p->id,
                    'department'      => $p->department,
                    'sheet_code'      => $p->sheet_code ?? '',
                    'budget_total'    => round((float)$p->budget_total + $children->sum('budget_total'), 2),
                    'own_budget'      => round((float)$p->budget_total, 2),
                    'budget_fund_101' => round((float)$p->budget_fund_101 + $children->sum('budget_fund_101'), 2),
                    'budget_fund_164' => round((float)$p->budget_fund_164 + $children->sum('budget_fund_164'), 2),
                    'budget_fund_161' => round((float)$p->budget_fund_161 + $children->sum('budget_fund_161'), 2),
                    'budget_fund_163' => round((float)$p->budget_fund_163 + $children->sum('budget_fund_163'), 2),
                    'parent_dept'     => null,
                    'is_parent'       => (bool)$p->is_parent,
                    'children'        => $children->map(fn($c) => [
                        'department'      => $c->department,
                        'sheet_code'      => $c->sheet_code ?? '',
                        'budget_total'    => round((float)$c->budget_total, 2),
                        'budget_fund_101' => round((float)$c->budget_fund_101, 2),
                        'budget_fund_164' => round((float)$c->budget_fund_164, 2),
                        'budget_fund_161' => round((float)$c->budget_fund_161, 2),
                        'budget_fund_163' => round((float)$c->budget_fund_163, 2),
                        'parent_dept'     => $c->parent_dept,
                    ])->values()->toArray(),
                ];
            })->sortByDesc('budget_total')->values()->toArray();

            $allByYear[$yr] = $combined;
        }

        // ── 2. Year summaries ─────────────────────────────────
        $yearTotals = DB::table($table)
            ->select('year',
                DB::raw('SUM(budget_total)    as total'),
                DB::raw('COUNT(*)             as dept_count'),
                DB::raw('SUM(budget_fund_101) as f101'),
                DB::raw('SUM(budget_fund_164) as f164'),
                DB::raw('SUM(budget_fund_161) as f161'),
                DB::raw('SUM(budget_fund_163) as f163'))
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->keyBy('year')
            ->map(fn($y) => [
                'year'       => (int) $y->year,
                'total'      => round((float) $y->total, 2),
                'dept_count' => (int) $y->dept_count,
                'f101'       => round((float) $y->f101, 2),
                'f164'       => round((float) $y->f164, 2),
                'f161'       => round((float) $y->f161, 2),
                'f163'       => round((float) $y->f163, 2),
            ]);

        // ── 3. YoY changes — match by sheet_code ─────────────
        // Build lookup: sheet_code -> budget per year
        $byCode = [];
        foreach ($years as $yr) {
            foreach ($allByYear[$yr] as $d) {
                $code = strtoupper(trim($d['sheet_code']));
                if (!isset($byCode[$code])) {
                    $byCode[$code] = [
                        'sheet_code' => $code,
                        'department' => $d['department'],
                    ];
                }
                $byCode[$code][$yr] = $d['budget_total'];
                $byCode[$code]['f101_'.$yr] = $d['budget_fund_101'];
                $byCode[$code]['f164_'.$yr] = $d['budget_fund_164'];
                $byCode[$code]['f161_'.$yr] = $d['budget_fund_161'];
                $byCode[$code]['f163_'.$yr] = $d['budget_fund_163'];
            }
        }

        // Build YoY rows — only depts present in at least 2025 & 2026
        $yoyRows = [];
        foreach ($byCode as $code => $d) {
            if (!isset($d[2025]) && !isset($d[2026])) continue;

            $b2024 = $d[2024] ?? null;
            $b2025 = $d[2025] ?? null;
            $b2026 = $d[2026] ?? null;

            $chg2526 = ($b2025 && $b2026)
                ? round(($b2026 - $b2025) / $b2025 * 100, 1) : null;
            $chg2425 = ($b2024 && $b2025)
                ? round(($b2025 - $b2024) / $b2024 * 100, 1) : null;

            $yoyRows[] = [
                'sheet_code'  => $code,
                'department'  => $d['department'],
                'budget_2024' => $b2024,
                'budget_2025' => $b2025,
                'budget_2026' => $b2026,
                'chg_25_26'   => $chg2526,
                'chg_24_25'   => $chg2425,
                'f101_2026'   => $d['f101_2026'] ?? 0,
                'f164_2026'   => $d['f164_2026'] ?? 0,
                'f161_2026'   => $d['f161_2026'] ?? 0,
                'f163_2026'   => $d['f163_2026'] ?? 0,
            ];
        }

        // Sort by 2026 budget desc
        usort($yoyRows, fn($a,$b) => ($b['budget_2026'] ?? 0) <=> ($a['budget_2026'] ?? 0));

        // ── 4. Top gainers & losers (2025→2026) ──────────────
        $withChg = array_filter($yoyRows, fn($r) => $r['chg_25_26'] !== null);
        usort($withChg, fn($a,$b) => $b['chg_25_26'] <=> $a['chg_25_26']);
        $withChg = array_values($withChg);

        $gainers = array_slice($withChg, 0, 5);
        $losers  = array_slice(array_reverse($withChg), 0, 5);

        // ── 5. Fund mix per dept (2026, top 15) ──────────────
        $fundMix = array_slice(
            array_filter($yoyRows, fn($r) => $r['budget_2026'] > 0),
            0, 15
        );

        return Inertia::render('Budget', [
            'allByYear'  => $allByYear,
            'yearTotals' => $yearTotals,
            'yoyRows'    => array_values($yoyRows),
            'gainers'    => $gainers,
            'losers'     => $losers,
            'fundMix'    => array_values($fundMix),
        ]);
    }
}