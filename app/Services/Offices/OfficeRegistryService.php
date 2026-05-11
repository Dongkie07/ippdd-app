<?php

namespace App\Services\Offices;

use App\Models\Office;
use App\Models\OfficeNameHistory;
use App\Support\OfficeNameResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OfficeRegistryService
{
    private const DEFAULT_EFFECTIVE_YEAR = 2000;
    private const MAX_SYNC_BATCH_SIZE = 100;

    public function findOrCreateFromName(?string $officeName, ?int $year = null): ?Office
    {
        $name = trim((string) $officeName);

        if ($name === '' || !$this->canUseOfficeRegistry()) {
            return null;
        }

        $canonicalName = OfficeNameResolver::canonicalName($name);
        $officeKey = OfficeNameResolver::key($name);
        $effectiveYear = $year ?: self::DEFAULT_EFFECTIVE_YEAR;

        $office = Office::query()->firstOrCreate(
            ['office_key' => $officeKey],
            [
                'current_name' => $canonicalName,
                'acronym' => $this->guessAcronym($name),
                'status' => Office::STATUS_ACTIVE,
            ],
        );

        $this->ensureHistoryName($office, $name, $effectiveYear, null, $office->acronym, $this->isCurrentName($name, $canonicalName));

        return $office;
    }

    public function renameOffice(Office $office, array $data): Office
    {
        $newName = trim($data['current_name']);
        $fromYear = (int) $data['effective_from_year'];
        $acronym = $data['acronym'] ?? null;
        $status = $data['status'] ?? Office::STATUS_ACTIVE;

        return DB::transaction(function () use ($office, $newName, $fromYear, $acronym, $status): Office {
            $this->closeCurrentHistory($office, $fromYear);

            $office->update([
                'current_name' => $newName,
                'acronym' => $acronym,
                'status' => $status,
            ]);

            $this->ensureHistoryName($office, $newName, $fromYear, null, $acronym, true);

            return $office->refresh();
        });
    }

    public function addHistoricalName(Office $office, array $data): OfficeNameHistory
    {
        $fromYear = (int) $data['effective_from_year'];
        $toYear = $data['effective_to_year'] ? (int) $data['effective_to_year'] : null;

        if ($toYear && $toYear < $fromYear) {
            throw ValidationException::withMessages([
                'effective_to_year' => 'The end year cannot be earlier than the start year.',
            ]);
        }

        return $this->ensureHistoryName(
            $office,
            trim($data['name']),
            $fromYear,
            $toYear,
            $data['acronym'] ?? null,
            false,
        );
    }

    public function syncFromSubmissions(): array
    {
        if (!$this->canSyncSubmissions()) {
            return ['created' => 0, 'linked' => 0];
        }

        $createdOfficeIds = [];
        $linkedRows = 0;

        DB::table('wfp_submissions')
            ->select('id', 'year', 'department')
            ->orderBy('id')
            ->chunkById(self::MAX_SYNC_BATCH_SIZE, function ($rows) use (&$createdOfficeIds, &$linkedRows): void {
                foreach ($rows as $row) {
                    $office = $this->findOrCreateFromName($row->department, (int) $row->year);

                    if (!$office) {
                        continue;
                    }

                    $createdOfficeIds[$office->id] = true;
                    DB::table('wfp_submissions')->where('id', $row->id)->update(['office_id' => $office->id]);
                    $linkedRows++;
                }
            });

        return ['created' => count($createdOfficeIds), 'linked' => $linkedRows];
    }

    public function assignOfficeToSubmission(int $submissionId, ?string $officeName, ?int $year): ?int
    {
        $office = $this->findOrCreateFromName($officeName, $year);

        if (!$office || !Schema::hasColumn('wfp_submissions', 'office_id')) {
            return null;
        }

        DB::table('wfp_submissions')->where('id', $submissionId)->update(['office_id' => $office->id]);

        return $office->id;
    }

    private function closeCurrentHistory(Office $office, int $newStartYear): void
    {
        OfficeNameHistory::query()
            ->where('office_id', $office->id)
            ->where('is_current', true)
            ->where(function ($query): void {
                $query->whereNull('effective_to_year')->orWhere('effective_to_year', '>=', now()->year);
            })
            ->update([
                'effective_to_year' => max(self::DEFAULT_EFFECTIVE_YEAR, $newStartYear - 1),
                'is_current' => false,
            ]);
    }

    private function ensureHistoryName(
        Office $office,
        string $name,
        int $fromYear,
        ?int $toYear,
        ?string $acronym,
        bool $isCurrent,
    ): OfficeNameHistory {
        return OfficeNameHistory::query()->updateOrCreate(
            [
                'office_id' => $office->id,
                'name' => $name,
                'effective_from_year' => $fromYear,
            ],
            [
                'acronym' => $acronym,
                'effective_to_year' => $toYear,
                'is_current' => $isCurrent,
            ],
        );
    }

    private function isCurrentName(string $name, string $canonicalName): bool
    {
        return OfficeNameResolver::normalize($name) === OfficeNameResolver::normalize($canonicalName);
    }

    private function guessAcronym(string $name): ?string
    {
        if (strlen($name) <= 12 && strtoupper($name) === $name) {
            return $name;
        }

        return null;
    }

    private function canUseOfficeRegistry(): bool
    {
        return Schema::hasTable('offices') && Schema::hasTable('office_name_histories');
    }

    private function canSyncSubmissions(): bool
    {
        return $this->canUseOfficeRegistry()
            && Schema::hasTable('wfp_submissions')
            && Schema::hasColumn('wfp_submissions', 'office_id');
    }
}
