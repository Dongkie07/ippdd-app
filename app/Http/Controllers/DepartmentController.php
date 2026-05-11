<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\OfficeNameHistory;
use App\Services\Offices\OfficeIdentityService;
use App\Services\Offices\OfficeRegistryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    private const MIN_YEAR = 2000;
    private const MAX_YEAR = 2099;
    private const INSERT_CHUNK_SIZE = 100;

    public function index(OfficeIdentityService $identity)
    {
        $years = DB::table('wfp_submissions')
            ->select('year')
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year');

        $departmentsByYear = [];

        foreach ($years as $year) {
            $departmentsByYear[$year] = DB::table('wfp_submissions')
                ->where('year', $year)
                ->orderByRaw('CASE WHEN parent_dept IS NULL THEN 0 ELSE 1 END')
                ->orderBy('no')
                ->get()
                ->map(fn ($department): array => $this->departmentPayload($department, (int) $year, $identity))
                ->toArray();
        }

        return Inertia::render('Departments', [
            'years' => $years,
            'deptsByYear' => $departmentsByYear,
            'offices' => $this->officeOptions(),
        ]);
    }

    public function store(Request $request, OfficeRegistryService $registry)
    {
        $validator = Validator::make($request->all(), $this->rules(true));

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $officeId = $this->resolveOfficeId($data, $registry);
        $payload = $this->submissionPayload($data, $officeId);

        DB::table('wfp_submissions')->insertGetId($payload);

        return redirect('/departments')
            ->with('flash', ['message' => "Department '{$data['department']}' added."]);
    }

    public function update(Request $request, int $id, OfficeRegistryService $registry)
    {
        $row = DB::table('wfp_submissions')->find($id);

        if (!$row) {
            return back()->withErrors(['id' => 'Record not found.']);
        }

        $validator = Validator::make($request->all(), $this->rules(false));

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $mergedData = array_merge((array) $row, $data);
        $officeId = $this->resolveOfficeId($mergedData, $registry, $row->office_id ?? null);

        DB::table('wfp_submissions')->where('id', $id)->update(
            $this->updatePayload($row, $data, $officeId),
        );

        return redirect('/departments')
            ->with('flash', ['message' => 'Department updated successfully.']);
    }

    public function destroy(int $id)
    {
        $row = DB::table('wfp_submissions')->find($id);

        if (!$row) {
            return back()->withErrors(['id' => 'Record not found.']);
        }

        DB::table('wfp_submissions')
            ->where('year', $row->year)
            ->where('parent_dept', $row->department)
            ->delete();

        DB::table('wfp_submissions')->where('id', $id)->delete();

        return redirect('/departments')
            ->with('flash', ['message' => "Deleted '{$row->department}' and its sub-offices."]);
    }

    public function destroyYear(int $year)
    {
        $count = DB::table('wfp_submissions')->where('year', $year)->count();

        if ($count === 0) {
            return back()->withErrors(['year' => "No data found for FY {$year}."]);
        }

        DB::table('wfp_submissions')->where('year', $year)->delete();

        return redirect('/departments')
            ->with('flash', ['message' => "All {$count} records for FY {$year} deleted."]);
    }

    public function storeYear(Request $request, OfficeIdentityService $identity)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:' . self::MIN_YEAR . '|max:' . self::MAX_YEAR,
            'copy_from' => 'nullable|integer|min:' . self::MIN_YEAR . '|max:' . self::MAX_YEAR,
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $newYear = (int) $request->year;
        $copyFrom = (int) ($request->copy_from ?? 0);

        if (DB::table('wfp_submissions')->where('year', $newYear)->exists()) {
            return back()->withErrors(['year' => "FY {$newYear} already exists."]);
        }

        if (!$copyFrom) {
            $this->createBlankYear($newYear);

            return redirect('/departments')
                ->with('flash', ['message' => "FY {$newYear} created successfully."]);
        }

        $sourceRows = DB::table('wfp_submissions')->where('year', $copyFrom)->orderBy('id')->get();

        if ($sourceRows->isEmpty()) {
            return back()->withErrors(['copy_from' => "FY {$copyFrom} has no data to copy from."]);
        }

        $this->copyYearStructure($sourceRows, $newYear, $identity);

        return redirect('/departments')
            ->with('flash', ['message' => "FY {$newYear} created with {$sourceRows->count()} departments from FY {$copyFrom}."]);
    }

    private function rules(bool $requireYear = true): array
    {
        return [
            'office_id' => 'nullable|integer',
            'year' => $requireYear ? 'required|integer|min:' . self::MIN_YEAR . '|max:' . self::MAX_YEAR : 'sometimes|integer',
            'department' => 'required|string|max:200',
            'sheet_code' => 'nullable|string|max:60',
            'parent_dept' => 'nullable|string|max:200',
            'is_parent' => 'boolean',
            'status' => 'nullable|in:Approved,Pending,For Revision',
            'remarks' => 'nullable|string|max:1000',
            'budget_fund_101' => 'nullable|numeric|min:0',
            'budget_fund_164' => 'nullable|numeric|min:0',
            'budget_fund_161' => 'nullable|numeric|min:0',
            'budget_fund_163' => 'nullable|numeric|min:0',
        ];
    }

    private function departmentPayload(object $department, int $year, OfficeIdentityService $identity): array
    {
        $officeMeta = $identity->metadata($department->office_id ?? null, $year, $department->department);

        return [
            'id' => $department->id,
            'office_id' => $officeMeta['office_id'],
            'office_key' => $officeMeta['office_key'],
            'current_name' => $officeMeta['current_name'],
            'historical_name' => $officeMeta['historical_name'],
            'no' => $department->no,
            'year' => (int) $department->year,
            'department' => $officeMeta['historical_name'],
            'recorded_department' => $department->department,
            'sheet_code' => $department->sheet_code,
            'parent_dept' => $department->parent_dept,
            'is_parent' => (bool) $department->is_parent,
            'status' => $department->status,
            'remarks' => $department->remarks,
            'budget_total' => (float) $department->budget_total,
            'budget_fund_101' => (float) $department->budget_fund_101,
            'budget_fund_164' => (float) $department->budget_fund_164,
            'budget_fund_161' => (float) $department->budget_fund_161,
            'budget_fund_163' => (float) $department->budget_fund_163,
        ];
    }

    private function submissionPayload(array $data, ?int $officeId): array
    {
        $now = now();
        $payload = [
            'year' => $data['year'],
            'no' => $data['sheet_code'] ?? '',
            'department' => trim($data['department']),
            'sheet_code' => $data['sheet_code'] ?? '',
            'parent_dept' => $data['parent_dept'] ?? null,
            'is_parent' => $data['is_parent'] ?? false,
            'status' => $data['status'] ?? 'Pending',
            'remarks' => $data['remarks'] ?? '',
            'budget_total' => $this->calculateTotal($data),
            'budget_fund_101' => $data['budget_fund_101'] ?? 0,
            'budget_fund_164' => $data['budget_fund_164'] ?? 0,
            'budget_fund_161' => $data['budget_fund_161'] ?? 0,
            'budget_fund_163' => $data['budget_fund_163'] ?? 0,
            'pi_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        return $this->withOfficeId($payload, $officeId);
    }

    private function updatePayload(object $row, array $data, ?int $officeId): array
    {
        $payload = [
            'department' => trim($data['department'] ?? $row->department),
            'sheet_code' => $data['sheet_code'] ?? $row->sheet_code,
            'parent_dept' => array_key_exists('parent_dept', $data) ? ($data['parent_dept'] ?: null) : $row->parent_dept,
            'is_parent' => $data['is_parent'] ?? $row->is_parent,
            'status' => $data['status'] ?? $row->status,
            'remarks' => $data['remarks'] ?? $row->remarks,
            'budget_total' => $this->calculateTotal(array_merge((array) $row, $data)),
            'budget_fund_101' => $data['budget_fund_101'] ?? $row->budget_fund_101,
            'budget_fund_164' => $data['budget_fund_164'] ?? $row->budget_fund_164,
            'budget_fund_161' => $data['budget_fund_161'] ?? $row->budget_fund_161,
            'budget_fund_163' => $data['budget_fund_163'] ?? $row->budget_fund_163,
            'updated_at' => now(),
        ];

        return $this->withOfficeId($payload, $officeId);
    }

    private function resolveOfficeId(array $data, OfficeRegistryService $registry, ?int $fallbackOfficeId = null): ?int
    {
        if (!Schema::hasColumn('wfp_submissions', 'office_id')) {
            return null;
        }

        if (!empty($data['office_id'])) {
            return (int) $data['office_id'];
        }

        $office = $registry->findOrCreateFromName($data['department'] ?? '', isset($data['year']) ? (int) $data['year'] : null);

        return $office?->id ?? $fallbackOfficeId;
    }

    private function createBlankYear(int $newYear): void
    {
        DB::table('wfp_submissions')->insert($this->withOfficeId([
            'year' => $newYear,
            'no' => '1',
            'department' => "FY {$newYear} — Add departments via manual entry or upload",
            'sheet_code' => '',
            'status' => 'Pending',
            'budget_total' => 0,
            'budget_fund_101' => 0,
            'budget_fund_164' => 0,
            'budget_fund_161' => 0,
            'budget_fund_163' => 0,
            'pi_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], null));
    }

    private function copyYearStructure($sourceRows, int $newYear, OfficeIdentityService $identity): void
    {
        $now = now();
        $parentNames = $this->parentNameMap($sourceRows, $newYear, $identity);

        $rows = $sourceRows->map(fn ($sourceRow): array => $this->copiedYearPayload($sourceRow, $newYear, $now, $parentNames, $identity))->toArray();

        foreach (array_chunk($rows, self::INSERT_CHUNK_SIZE) as $chunk) {
            DB::table('wfp_submissions')->insert($chunk);
        }
    }

    private function copiedYearPayload(object $sourceRow, int $newYear, $now, array $parentNames, OfficeIdentityService $identity): array
    {
        $officeId = $sourceRow->office_id ?? null;
        $departmentName = $identity->historicalName($officeId, $newYear, $sourceRow->department);

        $payload = [
            'year' => $newYear,
            'no' => $sourceRow->no,
            'department' => $departmentName,
            'sheet_code' => $sourceRow->sheet_code,
            'parent_dept' => $parentNames[$sourceRow->parent_dept] ?? $sourceRow->parent_dept,
            'is_parent' => $sourceRow->is_parent,
            'status' => 'Pending',
            'remarks' => '',
            'budget_total' => 0,
            'budget_fund_101' => 0,
            'budget_fund_164' => 0,
            'budget_fund_161' => 0,
            'budget_fund_163' => 0,
            'pi_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        return $this->withOfficeId($payload, $officeId);
    }

    private function parentNameMap($sourceRows, int $newYear, OfficeIdentityService $identity): array
    {
        return $sourceRows
            ->filter(fn ($row): bool => !$row->parent_dept)
            ->mapWithKeys(fn ($row): array => [
                $row->department => $identity->historicalName($row->office_id ?? null, $newYear, $row->department),
            ])
            ->toArray();
    }

    private function officeOptions(): array
    {
        if (!Schema::hasTable('offices')) {
            return [];
        }

        return Office::query()
            ->with('nameHistories')
            ->where('status', Office::STATUS_ACTIVE)
            ->orderBy('current_name')
            ->get()
            ->map(fn (Office $office): array => [
                'id' => $office->id,
                'office_key' => $office->office_key,
                'current_name' => $office->current_name,
                'acronym' => $office->acronym,
                'histories' => $office->nameHistories->map(fn (OfficeNameHistory $history): array => [
                    'name' => $history->name,
                    'acronym' => $history->acronym,
                    'effective_from_year' => $history->effective_from_year,
                    'effective_to_year' => $history->effective_to_year,
                ])->values(),
            ])->values()->toArray();
    }

    private function calculateTotal(array $data): float
    {
        return round(
            (float) ($data['budget_fund_101'] ?? 0)
            + (float) ($data['budget_fund_164'] ?? 0)
            + (float) ($data['budget_fund_161'] ?? 0)
            + (float) ($data['budget_fund_163'] ?? 0),
            2,
        );
    }

    private function withOfficeId(array $payload, ?int $officeId): array
    {
        if (Schema::hasColumn('wfp_submissions', 'office_id')) {
            $payload['office_id'] = $officeId;
        }

        return $payload;
    }
}
