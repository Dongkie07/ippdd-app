<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $table = 'wfp_submissions';

        $yearSummary = DB::table($table)
            ->select('year',
                DB::raw('SUM(budget_total)    as total_budget'),
                DB::raw('COUNT(*)             as dept_count'),
                DB::raw('SUM(budget_fund_101) as total_101'),
                DB::raw('SUM(budget_fund_164) as total_164'),
                DB::raw('SUM(budget_fund_161) as total_161'),
                DB::raw('SUM(budget_fund_163) as total_163'),
                DB::raw('AVG(budget_total)    as avg_budget'),
                DB::raw('MAX(budget_total)    as max_budget'))
            ->groupBy('year')->orderBy('year')->get()
            ->map(fn($y) => [
                'year'         => (int) $y->year,
                'total_budget' => round((float) $y->total_budget, 2),
                'dept_count'   => (int)  $y->dept_count,
                'total_101'    => round((float) $y->total_101, 2),
                'total_164'    => round((float) $y->total_164, 2),
                'total_161'    => round((float) $y->total_161, 2),
                'total_163'    => round((float) $y->total_163, 2),
                'avg_budget'   => round((float) $y->avg_budget, 2),
                'max_budget'   => round((float) $y->max_budget, 2),
            ]);

        $years    = $yearSummary->pluck('year')->toArray() ?: [2024, 2025, 2026];
        $deptData = [];
        foreach ($years as $yr) {
            $deptData[$yr] = DB::table($table)->where('year', $yr)->orderByDesc('budget_total')->limit(10)->get()
                ->map(fn($d) => [
                    'department'      => $d->department,
                    'sheet_code'      => $d->sheet_code ?? '',
                    'budget_total'    => round((float) $d->budget_total,    2),
                    'budget_fund_101' => round((float) $d->budget_fund_101, 2),
                    'budget_fund_164' => round((float) $d->budget_fund_164, 2),
                ])->toArray();
        }

        $allDepts = DB::table($table)->orderBy('year')->orderByDesc('budget_total')->get()
            ->map(fn($d) => [
                'id'              => $d->id,
                'year'            => (int) $d->year,
                'department'      => $d->department,
                'sheet_code'      => $d->sheet_code ?? '',
                'status'          => $d->status,
                'budget_total'    => round((float) $d->budget_total,    2),
                'budget_fund_101' => round((float) $d->budget_fund_101, 2),
                'budget_fund_164' => round((float) $d->budget_fund_164, 2),
                'budget_fund_161' => round((float) $d->budget_fund_161, 2),
                'budget_fund_163' => round((float) $d->budget_fund_163, 2),
            ]);

        return Inertia::render('Dashboard', [
            'yearSummary' => $yearSummary,
            'deptData'    => $deptData,
            'allDepts'    => $allDepts,
        ]);
    }
}