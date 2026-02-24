<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    // ── Shared data for all pages ─────────────────────────────
    private function sharedData(): array
    {
        $years = [2024, 2025, 2026];

        $yearSummary = DB::table('wfp_data')
            ->select(
                'year',
                DB::raw('SUM(budget) as total_budget'),
                DB::raw('SUM(pi_count) as total_pi'),
                DB::raw('COUNT(*) as dept_count')
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        $deptData = [];
        foreach ($years as $year) {
            $deptData[$year] = DB::table('wfp_data')
                ->where('year', $year)
                ->orderByDesc('budget')
                ->limit(10)
                ->get(['department', 'budget', 'pi_count', 'sheet_code']);
        }

        $allDepts = DB::table('wfp_data')
            ->orderBy('year')
            ->orderByDesc('budget')
            ->get(['year', 'department', 'budget', 'pi_count', 'sheet_code']);

        return compact('yearSummary', 'deptData', 'allDepts');
    }

    // ── Pages ─────────────────────────────────────────────────

    public function index()
    {
        return Inertia::render('Dashboard', $this->sharedData());
    }

    public function budget()
    {
        return Inertia::render('Budget', $this->sharedData());
    }

    public function indicators()
    {
        return Inertia::render('Indicators', $this->sharedData());
    }

    public function upload()
    {
        return Inertia::render('Upload', $this->sharedData());
    }

    public function reports()
    {
        return Inertia::render('Reports', $this->sharedData());
    }

    public function users()
    {
        return Inertia::render('Users', $this->sharedData());
    }

    public function settings()
    {
        return Inertia::render('Settings', $this->sharedData());
    }

    // ── Future: Process uploaded WFP Excel ───────────────────
    // public function processUpload(Request $request)
    // {
    //     $request->validate(['file' => 'required|mimes:xlsx,xls']);
    //     // Parse with maatwebsite/excel and seed into wfp_data
    //     // ...
    //     return redirect('/')->with('success', 'WFP data uploaded!');
    // }
}