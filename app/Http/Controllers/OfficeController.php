<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\OfficeNameHistory;
use App\Services\Offices\OfficeIdentityService;
use App\Services\Offices\OfficeRegistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class OfficeController extends Controller
{
    private const MIN_YEAR = 2000;
    private const MAX_YEAR = 2099;

    public function index(OfficeIdentityService $identity): Response
    {
        $offices = $this->officeTablesReady()
            ? Office::query()->with('nameHistories')->orderBy('current_name')->get()
            : collect();

        return Inertia::render('Offices', [
            'offices' => $offices->map(fn (Office $office): array => $this->officePayload($office))->values(),
            'needsMigration' => !$this->officeTablesReady(),
            'unlinkedSubmissionCount' => $this->unlinkedSubmissionCount(),
        ]);
    }

    public function store(Request $request, OfficeRegistryService $registry): RedirectResponse
    {
        $data = $request->validate($this->officeRules());

        $office = $registry->findOrCreateFromName($data['current_name'], (int) $data['effective_from_year']);

        if ($office) {
            $registry->renameOffice($office, $data);
        }

        return redirect('/offices')->with('flash', ['message' => 'Office identity saved.']);
    }

    public function update(Request $request, Office $office, OfficeRegistryService $registry): RedirectResponse
    {
        $data = $request->validate($this->officeRules());
        $registry->renameOffice($office, $data);

        return redirect('/offices')->with('flash', ['message' => 'Office current name updated.']);
    }

    public function storeHistory(Request $request, Office $office, OfficeRegistryService $registry): RedirectResponse
    {
        $data = $request->validate($this->historyRules());
        $registry->addHistoricalName($office, $data);

        return redirect('/offices')->with('flash', ['message' => 'Historical office name added.']);
    }

    public function destroyHistory(OfficeNameHistory $history): RedirectResponse
    {
        $history->delete();

        return redirect('/offices')->with('flash', ['message' => 'Historical office name removed.']);
    }

    public function sync(OfficeRegistryService $registry): RedirectResponse
    {
        $summary = $registry->syncFromSubmissions();

        return redirect('/offices')->with('flash', [
            'message' => "Office registry synced: {$summary['created']} offices, {$summary['linked']} WFP rows linked.",
        ]);
    }

    private function officePayload(Office $office): array
    {
        return [
            'id' => $office->id,
            'office_key' => $office->office_key,
            'current_name' => $office->current_name,
            'acronym' => $office->acronym,
            'status' => $office->status,
            'histories' => $office->nameHistories->map(fn (OfficeNameHistory $history): array => [
                'id' => $history->id,
                'name' => $history->name,
                'acronym' => $history->acronym,
                'effective_from_year' => $history->effective_from_year,
                'effective_to_year' => $history->effective_to_year,
                'is_current' => $history->is_current,
            ])->values(),
        ];
    }

    private function officeRules(): array
    {
        return [
            'current_name' => 'required|string|max:200',
            'acronym' => 'nullable|string|max:40',
            'status' => 'required|in:Active,Inactive',
            'effective_from_year' => 'required|integer|min:' . self::MIN_YEAR . '|max:' . self::MAX_YEAR,
        ];
    }

    private function historyRules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'acronym' => 'nullable|string|max:40',
            'effective_from_year' => 'required|integer|min:' . self::MIN_YEAR . '|max:' . self::MAX_YEAR,
            'effective_to_year' => 'nullable|integer|min:' . self::MIN_YEAR . '|max:' . self::MAX_YEAR,
        ];
    }

    private function unlinkedSubmissionCount(): int
    {
        if (!Schema::hasTable('wfp_submissions') || !Schema::hasColumn('wfp_submissions', 'office_id')) {
            return 0;
        }

        return DB::table('wfp_submissions')->whereNull('office_id')->count();
    }

    private function officeTablesReady(): bool
    {
        return Schema::hasTable('offices') && Schema::hasTable('office_name_histories');
    }
}
