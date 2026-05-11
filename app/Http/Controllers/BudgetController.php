<?php

namespace App\Http\Controllers;

use App\Services\Offices\OfficeIdentityService;
use App\Support\OfficeNameResolver;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BudgetController extends Controller
{
    private const WFP_TABLE = 'wfp_submissions';
    private const FUND_MIX_LIMIT = 15;
    private const CHANGE_DECIMAL_PLACES = 1;

    public function index(OfficeIdentityService $identity)
    {
        $years = $this->years();

        if (empty($years)) {
            return $this->emptyBudgetPage();
        }

        $allByYear = $this->allByYear($years, $identity);
        $yearTotals = $this->yearTotals();
        $yoyRows = $this->yearOverYearRows($years, $allByYear);
        $latestYear = max($years);
        $previousYear = $this->previousYear($years);
        $changeKey = $this->changeKey($previousYear, $latestYear);

        return Inertia::render('Budget', [
            'years' => $years,
            'allByYear' => $allByYear,
            'yearTotals' => $yearTotals,
            'yoyRows' => array_values($yoyRows),
            'gainers' => $this->topChanges($yoyRows, $changeKey),
            'losers' => $this->bottomChanges($yoyRows, $changeKey),
            'fundMix' => $this->fundMixRows($yoyRows, $latestYear),
        ]);
    }

    private function years(): array
    {
        return DB::table(self::WFP_TABLE)
            ->select('year')
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('year')
            ->map(fn ($year): int => (int) $year)
            ->toArray();
    }

    private function allByYear(array $years, OfficeIdentityService $identity): array
    {
        $groupedRows = [];

        foreach ($years as $year) {
            $rows = DB::table(self::WFP_TABLE)->where('year', $year)->get();
            $groupedRows[$year] = $this->parentRows($rows, $year, $identity)
                ->sortByDesc('budget_total')
                ->values()
                ->toArray();
        }

        return $groupedRows;
    }

    private function parentRows($rows, int $year, OfficeIdentityService $identity)
    {
        return $rows
            ->filter(fn ($row): bool => !$row->parent_dept)
            ->map(function ($parent) use ($rows, $year, $identity): array {
                $children = $rows->filter(fn ($row): bool => $row->parent_dept === $parent->department);
                $officeMeta = $identity->metadata($parent->office_id ?? null, $year, $parent->department);

                return [
                    'id' => $parent->id,
                    'office_id' => $officeMeta['office_id'],
                    'office_key' => $officeMeta['office_key'],
                    'department' => $officeMeta['historical_name'],
                    'current_name' => $officeMeta['current_name'],
                    'historical_name' => $officeMeta['historical_name'],
                    'recorded_department' => $parent->department,
                    'canonical_name' => $officeMeta['current_name'],
                    'sheet_code' => $parent->sheet_code ?? '',
                    'budget_total' => $this->sumBudget($parent, $children, 'budget_total'),
                    'own_budget' => round((float) $parent->budget_total, 2),
                    'budget_fund_101' => $this->sumBudget($parent, $children, 'budget_fund_101'),
                    'budget_fund_164' => $this->sumBudget($parent, $children, 'budget_fund_164'),
                    'budget_fund_161' => $this->sumBudget($parent, $children, 'budget_fund_161'),
                    'budget_fund_163' => $this->sumBudget($parent, $children, 'budget_fund_163'),
                    'parent_dept' => null,
                    'is_parent' => (bool) $parent->is_parent,
                    'children' => $this->childRows($children, $year, $identity),
                ];
            });
    }

    private function childRows($children, int $year, OfficeIdentityService $identity): array
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
                    'parent_dept' => $child->parent_dept,
                ];
            })
            ->values()
            ->toArray();
    }

    private function yearTotals()
    {
        return DB::table(self::WFP_TABLE)
            ->select(
                'year',
                DB::raw('SUM(budget_total) as total'),
                DB::raw('COUNT(*) as dept_count'),
                DB::raw('SUM(budget_fund_101) as f101'),
                DB::raw('SUM(budget_fund_164) as f164'),
                DB::raw('SUM(budget_fund_161) as f161'),
                DB::raw('SUM(budget_fund_163) as f163'),
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->keyBy('year')
            ->map(fn ($row): array => [
                'year' => (int) $row->year,
                'total' => round((float) $row->total, 2),
                'dept_count' => (int) $row->dept_count,
                'f101' => round((float) $row->f101, 2),
                'f164' => round((float) $row->f164, 2),
                'f161' => round((float) $row->f161, 2),
                'f163' => round((float) $row->f163, 2),
            ]);
    }

    private function yearOverYearRows(array $years, array $allByYear): array
    {
        $offices = $this->officeYearLookup($years, $allByYear);
        $latestYear = max($years);
        $previousYear = $this->previousYear($years);
        $rows = [];

        foreach ($offices as $officeKey => $office) {
            if (!$this->hasRecentBudget($office, $latestYear, $previousYear)) {
                continue;
            }

            $rows[] = array_merge(
                $this->budgetFields($office, $years),
                $this->changeFields($office, $years),
                [
                    'office_id' => $office['office_id'] ?? null,
                    'office_key' => $office['office_key'] ?? $officeKey,
                    'canonical_name' => $office['current_name'] ?? $office['department'],
                    'current_name' => $office['current_name'] ?? $office['department'],
                    'historical_name' => $office['historical_name'] ?? $office['department'],
                    'sheet_code' => $office['sheet_code'] ?? '',
                    'department' => $office['current_name'] ?? $office['department'],
                ],
            );
        }

        usort($rows, fn ($left, $right): int => ($right["budget_{$latestYear}"] ?? 0) <=> ($left["budget_{$latestYear}"] ?? 0));

        return $rows;
    }

    private function officeYearLookup(array $years, array $allByYear): array
    {
        $offices = [];
        $latestYear = max($years);

        foreach ($years as $year) {
            foreach ($allByYear[$year] as $row) {
                if (OfficeNameResolver::shouldSkip($row['department'])) {
                    continue;
                }

                $officeKey = $row['office_key'];
                $offices[$officeKey] ??= $this->baseOfficeLookupRow($row);

                if ($year === $latestYear) {
                    $offices[$officeKey]['department'] = $row['department'];
                    $offices[$officeKey]['current_name'] = $row['current_name'] ?? $row['department'];
                    $offices[$officeKey]['historical_name'] = $row['historical_name'] ?? $row['department'];
                }

                $this->storeYearFields($offices[$officeKey], $row, $year);
            }
        }

        return $offices;
    }

    private function baseOfficeLookupRow(array $row): array
    {
        return [
            'office_id' => $row['office_id'] ?? null,
            'office_key' => $row['office_key'],
            'current_name' => $row['current_name'] ?? $row['department'],
            'historical_name' => $row['historical_name'] ?? $row['department'],
            'sheet_code' => $row['sheet_code'] ?? '',
            'department' => $row['department'],
        ];
    }

    private function storeYearFields(array &$office, array $row, int $year): void
    {
        $office["budget_{$year}"] = $row['budget_total'];
        $office["f101_{$year}"] = $row['budget_fund_101'];
        $office["f164_{$year}"] = $row['budget_fund_164'];
        $office["f161_{$year}"] = $row['budget_fund_161'];
        $office["f163_{$year}"] = $row['budget_fund_163'];
    }

    private function budgetFields(array $office, array $years): array
    {
        $fields = [];

        foreach ($years as $year) {
            $fields["budget_{$year}"] = $office["budget_{$year}"] ?? null;
            $fields["f101_{$year}"] = $office["f101_{$year}"] ?? 0;
            $fields["f164_{$year}"] = $office["f164_{$year}"] ?? 0;
            $fields["f161_{$year}"] = $office["f161_{$year}"] ?? 0;
            $fields["f163_{$year}"] = $office["f163_{$year}"] ?? 0;
        }

        return $fields;
    }

    private function changeFields(array $office, array $years): array
    {
        $fields = [];

        for ($index = 0; $index < count($years) - 1; $index++) {
            $fromYear = $years[$index];
            $toYear = $years[$index + 1];
            $fromBudget = $office["budget_{$fromYear}"] ?? null;
            $toBudget = $office["budget_{$toYear}"] ?? null;
            $fields[$this->changeKey($fromYear, $toYear)] = $this->percentChange($fromBudget, $toBudget);
        }

        return $fields;
    }

    private function percentChange(?float $fromBudget, ?float $toBudget): ?float
    {
        if (!$fromBudget || !$toBudget) {
            return null;
        }

        return round(($toBudget - $fromBudget) / $fromBudget * 100, self::CHANGE_DECIMAL_PLACES);
    }

    private function topChanges(array $rows, ?string $changeKey): array
    {
        if (!$changeKey) {
            return [];
        }

        $filteredRows = $this->rowsWithChange($rows, $changeKey);
        usort($filteredRows, fn ($left, $right): int => ($right[$changeKey] ?? 0) <=> ($left[$changeKey] ?? 0));

        return array_slice(array_values($filteredRows), 0, 5);
    }

    private function bottomChanges(array $rows, ?string $changeKey): array
    {
        if (!$changeKey) {
            return [];
        }

        $filteredRows = $this->rowsWithChange($rows, $changeKey);
        usort($filteredRows, fn ($left, $right): int => ($left[$changeKey] ?? 0) <=> ($right[$changeKey] ?? 0));

        return array_slice(array_values($filteredRows), 0, 5);
    }

    private function rowsWithChange(array $rows, string $changeKey): array
    {
        return array_values(array_filter($rows, fn ($row): bool => ($row[$changeKey] ?? null) !== null));
    }

    private function fundMixRows(array $rows, int $latestYear): array
    {
        return array_values(array_slice(
            array_filter($rows, fn ($row): bool => ($row["budget_{$latestYear}"] ?? 0) > 0),
            0,
            self::FUND_MIX_LIMIT,
        ));
    }

    private function hasRecentBudget(array $office, int $latestYear, ?int $previousYear): bool
    {
        return isset($office["budget_{$latestYear}"])
            || ($previousYear && isset($office["budget_{$previousYear}"]));
    }

    private function previousYear(array $years): ?int
    {
        return count($years) >= 2 ? $years[count($years) - 2] : null;
    }

    private function changeKey(?int $fromYear, ?int $toYear): ?string
    {
        if (!$fromYear || !$toYear) {
            return null;
        }

        return 'chg_' . substr((string) $fromYear, 2) . '_' . substr((string) $toYear, 2);
    }

    private function sumBudget(object $parent, $children, string $column): float
    {
        return round((float) $parent->{$column} + (float) $children->sum($column), 2);
    }

    private function emptyBudgetPage()
    {
        return Inertia::render('Budget', [
            'years' => [],
            'allByYear' => [],
            'yearTotals' => [],
            'yoyRows' => [],
            'gainers' => [],
            'losers' => [],
            'fundMix' => [],
        ]);
    }
}
