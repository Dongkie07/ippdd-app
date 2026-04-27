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

        // ── 3. YoY changes — match by department name ────────
        // sheet_code is a positional row number that changes between years
        // so we match by normalized department name instead.
        // Known renames between years are handled via $aliases map.
        // ── Department rename aliases ─────────────────────────
        // Maps OLD name (any year) → CANONICAL name (latest year).
        // Add a new entry here whenever a department gets renamed.
        $aliases = [
            // 2024/2025 short codes → 2025 full names
            'OVPAA'   => 'OFFICE OF THE VICE PRESIDENT FOR ACADEMIC AFFAIRS',
            'OVPREP'  => 'OFFICE OF THE VICE PRESIDENT FOR RESEARCH, EXTENSION, AND PRODUCTION',
            'OVPAF'   => 'OFFICE OF THE VICE PRESIDENT FOR ADMINISTRATION AND FINANCE',

            // 2025 names → 2026 restructured names
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

            // 2024 seeder long names → 2025/2026 canonical names
            'OFFICE OF THE VICE PRESIDENT FOR ACADEMIC AFFAIRS'                => 'OVPAA',
            'OFFICE OF THE VICE PRESIDENT FOR RESEARCH, EXTENSION, AND PRODUCTION' => 'OVPREP',
            'OFFICE OF THE VICE PRESIDENT FOR ADMINISTRATION AND FINANCE'      => 'OVPAF',
            'RESEARCH DIVISION'                                                => 'RESEARCH DIVISION',
            'EXTENSION DIVISION'                                               => 'EXTENSION DIVISION',
            'PRODUCTION DIVISION'                                              => 'PRODUCTION DIVISION',
            'INSTITUTE OF ADVANCE STUDIES'                                     => 'IADS',
            'INSTITUTE OF COMPUTING'                                           => 'IC',
            'INSTITUTE OF LEADERSHIP, ENTREPRENEURSHIP, AND GOOD GOVERNANCE'  => 'ILEGG',
            'INSTITUTE OF TEACHER EDUCATION'                                   => 'ITED',
            'INSTITUTE OF AQUATIC AND APPLIED SCIENCES'                        => 'IAAS',
            'FINANCE SERVICES OFFICE'                                          => 'FINANCE SERVICES DIVISION',
            'OFFICE OF THE STUDENT DEVELOPMENT SERVICES'                       => 'STUDENT DEVELOPMENT AND SERVICES DIVISION',
            'QUALITY ASSURANCE OFFICE'                                         => 'QUALITY ASSURANCE DIVISION',
            'PLANNING AND RESOURCE MANAGEMENT OFFICE'                          => 'INSTITUTIONAL PLANNING AND PROJECT DEVELOPMENT DIVISION',
        ];

        // Skip non-department rows (footnotes, remarks picked up by parser)
        $skipPatterns = [
            'SOME OF THE OFFICES',
            'TO HAVE AN EFFECTIVE TRACING',
            'REFERENCES',
            'CY 20',
            'PERFORMANCE INDICATORS',
        ];
        // Reverse map: 2026 name => 2025 name

        // Normalize a department name for matching
        $normalize = fn($name) => strtoupper(trim(preg_replace('/\s+/', ' ', $name)));

        // Build lookup: normalized_name -> data per year
        $byName = [];
        foreach ($years as $yr) {
            foreach ($allByYear[$yr] as $d) {
                $name = $normalize($d['department']);

                // Apply alias: map old name to canonical (2026) name
                $canonical = $aliases[$name] ?? $name;  // fall back to name itself if no alias

                // Skip footnotes / remarks rows that the parser accidentally picked up
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
                // Prefer 2026 department name as the display name
                if ($yr === 2026) $byName[$canonical]['department'] = $d['department'];

                $byName[$canonical][$yr]            = $d['budget_total'];
                $byName[$canonical]['f101_'.$yr]    = $d['budget_fund_101'];
                $byName[$canonical]['f164_'.$yr]    = $d['budget_fund_164'];
                $byName[$canonical]['f161_'.$yr]    = $d['budget_fund_161'];
                $byName[$canonical]['f163_'.$yr]    = $d['budget_fund_163'];
            }
        }

        // Build YoY rows — only depts present in at least 2025 or 2026
        $yoyRows = [];
        foreach ($byName as $canonical => $d) {
            if (!isset($d[2025]) && !isset($d[2026])) continue;

            $b2024 = $d[2024] ?? null;
            $b2025 = $d[2025] ?? null;
            $b2026 = $d[2026] ?? null;

            $chg2526 = ($b2025 && $b2026)
                ? round(($b2026 - $b2025) / $b2025 * 100, 1) : null;
            $chg2425 = ($b2024 && $b2025)
                ? round(($b2025 - $b2024) / $b2024 * 100, 1) : null;

            $yoyRows[] = [
                'sheet_code'  => $d['sheet_code'] ?? '',
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