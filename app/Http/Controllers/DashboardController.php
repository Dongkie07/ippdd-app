<?php

namespace App\Http\Controllers;

use App\Services\Offices\OfficeIdentityService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private const WFP_TABLE = 'wfp_submissions';
    private const TOP_OFFICE_LIMIT = 10;

    public function index(OfficeIdentityService $identity)
    {
        $yearSummary = $this->yearSummary();
        $years = $yearSummary->pluck('year')->toArray() ?: [now()->year];

        return Inertia::render('Dashboard', [
            'yearSummary' => $yearSummary,
            'deptData' => $this->topOfficesByYear($years, $identity),
            'allDepts' => $this->allOffices($years, $identity),
        ]);
    }

    private function yearSummary()
    {
        return DB::table(self::WFP_TABLE)
            ->select(
                'year',
                DB::raw('SUM(budget_total) as total_budget'),
                DB::raw('SUM(CASE WHEN parent_dept IS NULL THEN 1 ELSE 0 END) as dept_count'),
                DB::raw('SUM(budget_fund_101) as total_101'),
                DB::raw('SUM(budget_fund_164) as total_164'),
                DB::raw('SUM(budget_fund_161) as total_161'),
                DB::raw('SUM(budget_fund_163) as total_163'),
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->map(fn ($row): array => [
                'year' => (int) $row->year,
                'total_budget' => round((float) $row->total_budget, 2),
                'dept_count' => (int) $row->dept_count,
                'total_101' => round((float) $row->total_101, 2),
                'total_164' => round((float) $row->total_164, 2),
                'total_161' => round((float) $row->total_161, 2),
                'total_163' => round((float) $row->total_163, 2),
                'avg_budget' => 0,
                'max_budget' => 0,
            ]);
    }

    private function topOfficesByYear(array $years, OfficeIdentityService $identity): array
    {
        $topOffices = [];

        foreach ($years as $year) {
            $rows = DB::table(self::WFP_TABLE)->where('year', $year)->get();
            $topOffices[$year] = $this->combinedParentRows($rows, (int) $year, $identity)
                ->sortByDesc('budget_total')
                ->values()
                ->take(self::TOP_OFFICE_LIMIT)
                ->toArray();
        }

        return $topOffices;
    }

    private function allOffices(array $years, OfficeIdentityService $identity)
    {
        return collect($years)
            ->flatMap(fn ($year) => $this->combinedRowsForYear((int) $year, $identity))
            ->sortBy(['year', 'department'])
            ->values();
    }

    private function combinedRowsForYear(int $year, OfficeIdentityService $identity)
    {
        $rows = DB::table(self::WFP_TABLE)->where('year', $year)->get();

        return $this->combinedParentRows($rows, $year, $identity, true);
    }

    private function combinedParentRows($rows, int $year, OfficeIdentityService $identity, bool $useHistoricalName = false)
    {
        return $rows
            ->filter(fn ($row): bool => !$row->parent_dept)
            ->map(function ($parent) use ($rows, $year, $identity, $useHistoricalName): array {
                $children = $this->childrenOf($rows, $parent->department);
                $officeMeta = $identity->metadata($parent->office_id ?? null, $year, $parent->department);
                $displayName = $useHistoricalName ? $officeMeta['historical_name'] : $officeMeta['current_name'];

                return [
                    'id' => $parent->id,
                    'year' => (int) $parent->year,
                    'office_id' => $officeMeta['office_id'],
                    'office_key' => $officeMeta['office_key'],
                    'department' => $displayName,
                    'current_name' => $officeMeta['current_name'],
                    'historical_name' => $officeMeta['historical_name'],
                    'recorded_department' => $parent->department,
                    'canonical_name' => $officeMeta['current_name'],
                    'sheet_code' => $parent->sheet_code ?? '',
                    'status' => $parent->status,
                    'budget_total' => $this->sumBudget($parent, $children, 'budget_total'),
                    'own_budget' => round((float) $parent->budget_total, 2),
                    'children_budget' => round((float) $children->sum('budget_total'), 2),
                    'budget_fund_101' => $this->sumBudget($parent, $children, 'budget_fund_101'),
                    'budget_fund_164' => $this->sumBudget($parent, $children, 'budget_fund_164'),
                    'budget_fund_161' => $this->sumBudget($parent, $children, 'budget_fund_161'),
                    'budget_fund_163' => $this->sumBudget($parent, $children, 'budget_fund_163'),
                    'children' => $this->childPayloads($children, $year, $identity),
                ];
            });
    }

    private function childrenOf($rows, string $parentName)
    {
        return $rows->filter(fn ($row): bool => $row->parent_dept === $parentName);
    }

    private function childPayloads($children, int $year, OfficeIdentityService $identity): array
    {
        return $children
            ->map(function ($child) use ($year, $identity): array {
                $officeMeta = $identity->metadata($child->office_id ?? null, $year, $child->department);

                return [
                    'office_id' => $officeMeta['office_id'],
                    'office_key' => $officeMeta['office_key'],
                    'department' => $officeMeta['historical_name'],
                    'current_name' => $officeMeta['current_name'],
                    'historical_name' => $officeMeta['historical_name'],
                    'recorded_department' => $child->department,
                    'canonical_name' => $officeMeta['current_name'],
                    'parent_office_key' => $identity->key(null, $child->parent_dept),
                    'sheet_code' => $child->sheet_code ?? '',
                    'budget_total' => round((float) $child->budget_total, 2),
                    'budget_fund_101' => round((float) $child->budget_fund_101, 2),
                    'budget_fund_164' => round((float) $child->budget_fund_164, 2),
                    'budget_fund_161' => round((float) $child->budget_fund_161, 2),
                    'budget_fund_163' => round((float) $child->budget_fund_163, 2),
                ];
            })
            ->values()
            ->toArray();
    }

    private function sumBudget(object $parent, $children, string $column): float
    {
        return round((float) $parent->{$column} + (float) $children->sum($column), 2);
    }
}
