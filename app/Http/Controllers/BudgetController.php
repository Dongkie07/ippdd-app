<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function index()
    {
        $table = 'wfp_submissions';

        // ── Years: read dynamically from DB ──────────────────────
        $years = DB::table($table)
            ->select('year')->groupBy('year')->orderBy('year')
            ->pluck('year')->map(fn($y) => (int)$y)->toArray();

        if (empty($years)) {
            return Inertia::render('Budget', [
                'years' => [], 'allByYear' => [], 'yearTotals' => [],
                'yoyRows' => [], 'gainers' => [], 'losers' => [], 'fundMix' => [],
            ]);
        }

        // ── 1. All departments per year (combined parent+children) ──
        $allByYear = [];
        foreach ($years as $yr) {
            $rawRows = DB::table($table)->where('year', $yr)->get();
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

        // ── 2. Year summaries ─────────────────────────────────────
        $yearTotals = DB::table($table)
            ->select('year',
                DB::raw('SUM(budget_total)    as total'),
                DB::raw('COUNT(*)             as dept_count'),
                DB::raw('SUM(budget_fund_101) as f101'),
                DB::raw('SUM(budget_fund_164) as f164'),
                DB::raw('SUM(budget_fund_161) as f161'),
                DB::raw('SUM(budget_fund_163) as f163'))
            ->groupBy('year')->orderBy('year')->get()
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

        // ── 3. YoY changes — fully dynamic, no hardcoded years ───
        $aliases = [
            'OVPAA'   => 'OFFICE OF THE VICE PRESIDENT FOR ACADEMIC AFFAIRS',
            'OVPREP'  => 'OFFICE OF THE VICE PRESIDENT FOR RESEARCH, EXTENSION, AND PRODUCTION',
            'OVPAF'   => 'OFFICE OF THE VICE PRESIDENT FOR ADMINISTRATION AND FINANCE',
            'RESEARCH'                  => 'RESEARCH DIVISION',
            'EXTENSION'                 => 'EXTENSION DIVISION',
            'EXECUTIVE'                 => 'PRESIDENTIAL AFFAIRS DIVISION',
            'EXECUTIVE AFFAIRS'         => 'PRESIDENTIAL AFFAIRS DIVISION',
            'FINANCE'                   => 'FINANCE SERVICES DIVISION',
            'FINANCE SERVICES OFFICE'   => 'FINANCE SERVICES DIVISION',
            'ADMINISTRATIVE'            => 'ADMINISTRATIVE SERVICES DIVISION',
            'ADMINISTRATIVE SERVICES'   => 'ADMINISTRATIVE SERVICES DIVISION',
            'QAO'                       => 'QUALITY ASSURANCE DIVISION',
            'QUALITY ASSURANCE OFFICE'  => 'QUALITY ASSURANCE DIVISION',
            'OSDS'                      => 'STUDENT DEVELOPMENT AND SERVICES DIVISION',
            'OFFICE OF THE STUDENT DEVELOPMENT SERVICES' => 'STUDENT DEVELOPMENT AND SERVICES DIVISION',
            'PRMO'                      => 'INSTITUTIONAL PLANNING AND PROJECT DEVELOPMENT DIVISION',
            'PLANNING AND RESOURCE MANAGEMENT OFFICE' => 'INSTITUTIONAL PLANNING AND PROJECT DEVELOPMENT DIVISION',
            'CURRICULUM'                => 'CURRICULUM AND INSTRUCTION DIVISION',
            'IADS'                      => 'IADS',
            'ITED'                      => 'ITED',
            'OFFICE OF THE VICE PRESIDENT FOR ACADEMIC AFFAIRS'                => 'OVPAA',
            'OFFICE OF THE VICE PRESIDENT FOR RESEARCH, EXTENSION, AND PRODUCTION' => 'OVPREP',
            'OFFICE OF THE VICE PRESIDENT FOR ADMINISTRATION AND FINANCE'      => 'OVPAF',
            'RESEARCH DIVISION'         => 'RESEARCH DIVISION',
            'EXTENSION DIVISION'        => 'EXTENSION DIVISION',
            'PRODUCTION DIVISION'       => 'PRODUCTION DIVISION',
            'INSTITUTE OF ADVANCE STUDIES'    => 'IADS',
            'INSTITUTE OF COMPUTING'          => 'IC',
            'INSTITUTE OF LEADERSHIP, ENTREPRENEURSHIP, AND GOOD GOVERNANCE' => 'ILEGG',
            'INSTITUTE OF TEACHER EDUCATION'  => 'ITED',
            'INSTITUTE OF AQUATIC AND APPLIED SCIENCES' => 'IAAS',
            'FINANCE SERVICES OFFICE'   => 'FINANCE SERVICES DIVISION',
            'OFFICE OF THE STUDENT DEVELOPMENT SERVICES' => 'STUDENT DEVELOPMENT AND SERVICES DIVISION',
            'QUALITY ASSURANCE OFFICE'  => 'QUALITY ASSURANCE DIVISION',
            'PLANNING AND RESOURCE MANAGEMENT OFFICE' => 'INSTITUTIONAL PLANNING AND PROJECT DEVELOPMENT DIVISION',
        ];

        $skipPatterns = [
            'SOME OF THE OFFICES', 'TO HAVE AN EFFECTIVE TRACING',
            'REFERENCES', 'CY 20', 'PERFORMANCE INDICATORS',
        ];

        $normalize = fn($name) => strtoupper(trim(preg_replace('/\s+/', ' ', $name)));

        // Build lookup: canonical_name -> per-year data
        $byName = [];
        foreach ($years as $yr) {
            foreach ($allByYear[$yr] as $d) {
                $name      = $normalize($d['department']);
                $canonical = $aliases[$name] ?? $name;

                $skip = false;
                foreach ($skipPatterns as $pattern) {
                    if (str_contains($canonical, $pattern)) { $skip = true; break; }
                }
                if ($skip) continue;

                if (!isset($byName[$canonical])) {
                    $byName[$canonical] = [
                        'sheet_code' => $d['sheet_code'] ?? '',
                        'department' => $d['department'],
                    ];
                }
                // Prefer the latest year's display name
                if ($yr === max($years)) $byName[$canonical]['department'] = $d['department'];

                // Store all fund fields dynamically
                $byName[$canonical]["budget_{$yr}"] = $d['budget_total'];
                $byName[$canonical]["f101_{$yr}"]   = $d['budget_fund_101'];
                $byName[$canonical]["f164_{$yr}"]   = $d['budget_fund_164'];
                $byName[$canonical]["f161_{$yr}"]   = $d['budget_fund_161'];
                $byName[$canonical]["f163_{$yr}"]   = $d['budget_fund_163'];
            }
        }

        $latestYear = max($years);
        $prevYear   = count($years) >= 2 ? $years[count($years) - 2] : null;

        $yoyRows = [];
        foreach ($byName as $canonical => $d) {
            // Must exist in at least the latest or previous year
            if (!isset($d["budget_{$latestYear}"]) && (!$prevYear || !isset($d["budget_{$prevYear}"]))) continue;

            // ── Build ALL dynamic year fields ──────────────────────
            $yearFields = [];
            foreach ($years as $yr) {
                $yearFields["budget_{$yr}"] = $d["budget_{$yr}"] ?? null;
                $yearFields["f101_{$yr}"]   = $d["f101_{$yr}"]   ?? 0;
                $yearFields["f164_{$yr}"]   = $d["f164_{$yr}"]   ?? 0;
                $yearFields["f161_{$yr}"]   = $d["f161_{$yr}"]   ?? 0;
                $yearFields["f163_{$yr}"]   = $d["f163_{$yr}"]   ?? 0;
            }

            // ── Build change fields for every consecutive year pair ─
            $changeFields = [];
            for ($i = 0; $i < count($years) - 1; $i++) {
                $yr1  = $years[$i];
                $yr2  = $years[$i + 1];
                $b1   = $d["budget_{$yr1}"] ?? null;
                $b2   = $d["budget_{$yr2}"] ?? null;
                $s1   = substr((string)$yr1, 2);
                $s2   = substr((string)$yr2, 2);
                $changeFields["chg_{$s1}_{$s2}"] = ($b1 && $b2)
                    ? round(($b2 - $b1) / $b1 * 100, 1)
                    : null;
            }

            $yoyRows[] = array_merge($yearFields, $changeFields, [
                'sheet_code' => $d['sheet_code'] ?? '',
                'department' => $d['department'],
            ]);
        }

        // Sort by latest year budget desc
        usort($yoyRows, fn($a, $b) =>
            ($b["budget_{$latestYear}"] ?? 0) <=> ($a["budget_{$latestYear}"] ?? 0)
        );

        // ── 4. Top gainers & losers (latest 2 years) ─────────────
        $chgKey  = $prevYear
            ? 'chg_' . substr((string)$prevYear, 2) . '_' . substr((string)$latestYear, 2)
            : null;

        $withChg = $chgKey
            ? array_filter($yoyRows, fn($r) => ($r[$chgKey] ?? null) !== null)
            : [];

        usort($withChg, fn($a, $b) => ($b[$chgKey] ?? 0) <=> ($a[$chgKey] ?? 0));
        $withChg = array_values($withChg);
        $gainers = array_slice($withChg, 0, 5);
        $losers  = array_slice(array_reverse($withChg), 0, 5);

        // ── 5. Fund mix per dept (latest year, top 15) ───────────
        $fundMix = array_slice(
            array_filter($yoyRows, fn($r) => ($r["budget_{$latestYear}"] ?? 0) > 0),
            0, 15
        );

        return Inertia::render('Budget', [
            'years'      => $years,
            'allByYear'  => $allByYear,
            'yearTotals' => $yearTotals,
            'yoyRows'    => array_values($yoyRows),
            'gainers'    => $gainers,
            'losers'     => $losers,
            'fundMix'    => array_values($fundMix),
        ]);
    }
}