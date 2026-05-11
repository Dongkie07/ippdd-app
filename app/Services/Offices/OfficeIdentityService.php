<?php

namespace App\Services\Offices;

use App\Models\Office;
use App\Models\OfficeNameHistory;
use App\Support\OfficeNameResolver;
use Illuminate\Support\Facades\Schema;

class OfficeIdentityService
{
    private const UNKNOWN_OFFICE_KEY = 'UNKNOWN_OFFICE';

    private array $officeCache = [];
    private array $historyCache = [];

    public function metadata(?int $officeId, ?int $year, ?string $fallbackName): array
    {
        $office = $this->office($officeId);
        $fallback = trim((string) $fallbackName);

        if (!$office) {
            return $this->fallbackMetadata($fallback);
        }

        return [
            'office_id' => $office->id,
            'office_key' => $office->office_key,
            'canonical_name' => $office->current_name,
            'current_name' => $office->current_name,
            'historical_name' => $this->historicalName($office->id, $year, $fallback ?: $office->current_name),
        ];
    }

    public function currentName(?int $officeId, string $fallbackName): string
    {
        return $this->metadata($officeId, null, $fallbackName)['current_name'];
    }

    public function historicalName(?int $officeId, ?int $year, string $fallbackName): string
    {
        if (!$officeId || !$year || !$this->canUseOfficeTables()) {
            return $fallbackName;
        }

        $history = $this->historyForYear($officeId, $year);

        return $history?->name ?? $fallbackName;
    }

    public function key(?int $officeId, ?string $fallbackName): string
    {
        $office = $this->office($officeId);

        return $office?->office_key ?? OfficeNameResolver::key($fallbackName);
    }

    private function fallbackMetadata(string $name): array
    {
        $canonicalName = OfficeNameResolver::canonicalName($name);

        return [
            'office_id' => null,
            'office_key' => OfficeNameResolver::key($name) ?: self::UNKNOWN_OFFICE_KEY,
            'canonical_name' => $canonicalName,
            'current_name' => $canonicalName,
            'historical_name' => $name ?: $canonicalName,
        ];
    }

    private function office(?int $officeId): ?Office
    {
        if (!$officeId || !$this->canUseOfficeTables()) {
            return null;
        }

        if (!array_key_exists($officeId, $this->officeCache)) {
            $this->officeCache[$officeId] = Office::query()->find($officeId);
        }

        return $this->officeCache[$officeId];
    }

    private function historyForYear(int $officeId, int $year): ?OfficeNameHistory
    {
        return collect($this->histories($officeId))
            ->first(fn (OfficeNameHistory $history): bool => $this->historyCoversYear($history, $year));
    }

    private function histories(int $officeId): array
    {
        if (!array_key_exists($officeId, $this->historyCache)) {
            $this->historyCache[$officeId] = OfficeNameHistory::query()
                ->where('office_id', $officeId)
                ->orderByDesc('effective_from_year')
                ->get()
                ->all();
        }

        return $this->historyCache[$officeId];
    }

    private function historyCoversYear(OfficeNameHistory $history, int $year): bool
    {
        $startsBeforeYear = $history->effective_from_year <= $year;
        $endsAfterYear = !$history->effective_to_year || $history->effective_to_year >= $year;

        return $startsBeforeYear && $endsAfterYear;
    }

    private function canUseOfficeTables(): bool
    {
        return Schema::hasTable('offices') && Schema::hasTable('office_name_histories');
    }
}
