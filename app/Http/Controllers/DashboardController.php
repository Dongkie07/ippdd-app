<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $table = 'wfp_submissions';

        // ── Year summary ──────────────────────────────────────────────
        // Sum only TOP-LEVEL rows (parent_dept IS NULL) to avoid
        // double-counting children whose budgets are separate from parent.
        // Combined total per parent = parent own + all its children,
        // computed via subquery.
        $yearSummary = DB::table($table)
            ->select(
                'year',
                // Total = sum of ALL rows (each row has its own budget, no double count)
                // parent row budget = just the main office portion
                // child row budget  = the sub-office portion
                // So grand total = SUM of every row = correct full total
                DB::raw('SUM(budget_total)    as total_budget'),
                // Count only top-level departments for "reporting offices" KPI
                DB::raw('SUM(CASE WHEN parent_dept IS NULL THEN 1 ELSE 0 END) as dept_count'),
                DB::raw('SUM(budget_fund_101) as total_101'),
                DB::raw('SUM(budget_fund_164) as total_164'),
                DB::raw('SUM(budget_fund_161) as total_161'),
                DB::raw('SUM(budget_fund_163) as total_163')
            )
            ->groupBy('year')->orderBy('year')->get()
            ->map(fn($y) => [
                'year'         => (int) $y->year,
                'total_budget' => round((float) $y->total_budget, 2),
                'dept_count'   => (int)  $y->dept_count,
                'total_101'    => round((float) $y->total_101, 2),
                'total_164'    => round((float) $y->total_164, 2),
                'total_161'    => round((float) $y->total_161, 2),
                'total_163'    => round((float) $y->total_163, 2),
                'avg_budget'   => 0, // calculated below if needed
                'max_budget'   => 0,
            ]);

        $years = $yearSummary->pluck('year')->toArray() ?: [2024, 2025, 2026];

        // ── Top 10 departments per year ───────────────────────────────
        // For each top-level dept, compute combined total = own + children
        $deptData = [];
        foreach ($years as $yr) {
            // Get all rows for this year
            $allYearRows = DB::table($table)->where('year', $yr)->get();

            // Build combined totals: for each top-level dept, add children budgets
            $parents = $allYearRows->filter(fn($r) => !$r->parent_dept);
            $combined = $parents->map(function($p) use ($allYearRows) {
                $childTotal = $allYearRows
                    ->filter(fn($r) => $r->parent_dept === $p->department)
                    ->sum('budget_total');
                return [
                    'department'      => $p->department,
                    'sheet_code'      => $p->sheet_code ?? '',
                    'budget_total'    => round((float)$p->budget_total + $childTotal, 2),
                    'own_budget'      => round((float)$p->budget_total, 2),
                    'children_budget' => round($childTotal, 2),
                    'budget_fund_101' => round((float)$p->budget_fund_101, 2),
                    'budget_fund_164' => round((float)$p->budget_fund_164, 2),
                ];
            })->sortByDesc('budget_total')->values()->take(10)->toArray();

            $deptData[$yr] = $combined;
        }

        // ── All departments (for table) ───────────────────────────────
        // Return top-level only, with combined totals
        $allDepts = collect();
        foreach ($years as $yr) {
            $allYearRows = DB::table($table)->where('year', $yr)->get();
            $parents = $allYearRows->filter(fn($r) => !$r->parent_dept);
            $parents->each(function($p) use ($allYearRows, $allDepts, $yr) {
                $childTotal  = $allYearRows->filter(fn($r) => $r->parent_dept === $p->department)->sum('budget_total');
                $childF101   = $allYearRows->filter(fn($r) => $r->parent_dept === $p->department)->sum('budget_fund_101');
                $childF164   = $allYearRows->filter(fn($r) => $r->parent_dept === $p->department)->sum('budget_fund_164');
                $childF161   = $allYearRows->filter(fn($r) => $r->parent_dept === $p->department)->sum('budget_fund_161');
                $childF163   = $allYearRows->filter(fn($r) => $r->parent_dept === $p->department)->sum('budget_fund_163');
                $children = $allYearRows->filter(fn($r) => $r->parent_dept === $p->department)
                    ->map(fn($c) => [
                        'department'      => $c->department,
                        'sheet_code'      => $c->sheet_code ?? '',
                        'budget_total'    => round((float)$c->budget_total, 2),
                        'budget_fund_101' => round((float)$c->budget_fund_101, 2),
                        'budget_fund_164' => round((float)$c->budget_fund_164, 2),
                        'budget_fund_161' => round((float)$c->budget_fund_161, 2),
                        'budget_fund_163' => round((float)$c->budget_fund_163, 2),
                    ])->values()->toArray();

                $allDepts->push([
                    'id'              => $p->id,
                    'year'            => (int) $p->year,
                    'department'      => $p->department,
                    'sheet_code'      => $p->sheet_code ?? '',
                    'status'          => $p->status,
                    'budget_total'    => round((float)$p->budget_total + $childTotal, 2),
                    'own_budget'      => round((float)$p->budget_total, 2),
                    'budget_fund_101' => round((float)$p->budget_fund_101 + $childF101, 2),
                    'budget_fund_164' => round((float)$p->budget_fund_164 + $childF164, 2),
                    'budget_fund_161' => round((float)$p->budget_fund_161 + $childF161, 2),
                    'budget_fund_163' => round((float)$p->budget_fund_163 + $childF163, 2),
                    'children'        => $children,
                ]);
            });
        }

        return Inertia::render('Dashboard', [
            'yearSummary' => $yearSummary,
            'deptData'    => $deptData,
            'allDepts'    => $allDepts->sortBy(['year','department'])->values(),
        ]);
    }
}