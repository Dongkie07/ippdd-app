<?php

namespace App\Services\Wfp;

use App\Services\Offices\OfficeRegistryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Handles database writes for confirmed WFP imports.
 * The controller should not know insert-column details. It has suffered enough.
 */
class WfpImportPersister
{
    public function __construct(private readonly OfficeRegistryService $officeRegistry)
    {
    }

    public function save(array $rows, string $filename): array
    {
        $year = (int) ($rows[0]['year'] ?? 0);

        if ($year <= 0) {
            throw new RuntimeException('The selected rows do not contain a valid fiscal year.');
        }

        return DB::transaction(function () use ($rows, $filename, $year) {
            $this->deleteExistingYear($year);

            $summary = [
                'year'         => $year,
                'filename'     => $filename,
                'dept_count'   => 0,
                'pi_count'     => 0,
                'total_budget' => 0,
            ];

            foreach ($rows as $row) {
                if (!($row['selected'] ?? true)) {
                    continue;
                }

                $pis = $row['pis'] ?? [];
                $submissionId = $this->insertSubmission($row, $year, count($pis));

                $this->insertPerformanceIndicators($submissionId, $row, $pis, $year);

                $summary['dept_count']++;
                $summary['pi_count'] += count($pis);
                $summary['total_budget'] += $this->amount($row, 'budget_total', 'budget');
            }

            $summary['message'] = "FY {$year} saved — {$summary['dept_count']} departments, {$summary['pi_count']} PIs.";

            return $summary;
        });
    }

    private function deleteExistingYear(int $year): void
    {
        $submissionIds = DB::table('wfp_submissions')->where('year', $year)->pluck('id');

        if (Schema::hasTable('wfp_pis') && $submissionIds->count()) {
            DB::table('wfp_pis')->whereIn('submission_id', $submissionIds)->delete();
        }

        DB::table('wfp_submissions')->where('year', $year)->delete();
    }

    private function insertSubmission(array $row, int $year, int $piCount): int
    {
        $now = now();

        $payload = [
            'year'            => $year,
            'no'              => $row['no'] ?? null,
            'department'      => $row['department'],
            'sheet_code'      => $row['sheet_code'] ?? $this->makeSheetCode($row['department']),
            'status'          => $row['status'] ?? 'Pending',
            'remarks'         => $row['remarks'] ?? null,
            'parent_dept'     => $row['parent_dept'] ?? null,
            'is_parent'       => (bool) ($row['is_parent'] ?? false),
            'budget_total'    => $this->amount($row, 'budget_total', 'budget'),
            'budget_fund_101' => $this->amount($row, 'budget_fund_101', 'fund_101'),
            'budget_fund_164' => $this->amount($row, 'budget_fund_164', 'fund_164'),
            'budget_fund_161' => $this->amount($row, 'budget_fund_161', 'fund_161'),
            'budget_fund_163' => $this->amount($row, 'budget_fund_163', 'fund_163'),
            'pi_count'        => $piCount,
            'created_at'      => $now,
            'updated_at'      => $now,
        ];

        if (Schema::hasColumn('wfp_submissions', 'office_id')) {
            $office = $this->officeRegistry->findOrCreateFromName($row['department'], $year);
            $payload['office_id'] = $office?->id;
        }

        return DB::table('wfp_submissions')->insertGetId($payload);
    }

    private function insertPerformanceIndicators(int $submissionId, array $row, array $pis, int $year): void
    {
        if (!Schema::hasTable('wfp_pis') || empty($pis)) {
            return;
        }

        $now = now();
        $records = array_map(fn (array $pi) => [
            'submission_id'    => $submissionId,
            'year'             => $year,
            'department'       => $row['department'],
            'seq'              => $pi['seq'] ?? 0,
            'code'             => $pi['code'] ?? null,
            'reference_source' => $pi['reference'] ?? null,
            'description'      => $pi['description'] ?? '',
            'definition'       => $pi['definition'] ?? null,
            'target'           => $pi['target'] ?? null,
            'created_at'       => $now,
            'updated_at'       => $now,
        ], $pis);

        DB::table('wfp_pis')->insert($records);
    }

    private function amount(array $row, string $preferredKey, string $fallbackKey): float
    {
        return (float) ($row[$preferredKey] ?? $row[$fallbackKey] ?? 0);
    }

    private function makeSheetCode(string $department): string
    {
        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $department), 0, 20));
    }
}
