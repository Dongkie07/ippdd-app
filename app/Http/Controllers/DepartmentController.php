<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

/**
 * DepartmentController
 * ────────────────────────────────────────────────────────────
 * Handles all manual CRUD operations on wfp_submissions:
 *   GET  /departments              → index (list + form page)
 *   POST /departments              → store (create new entry)
 *   PUT  /departments/{id}         → update (edit existing)
 *   DELETE /departments/{id}       → destroy (delete one row)
 *   DELETE /departments/year/{yr}  → destroyYear (delete all rows for a year)
 *   POST /departments/year         → storeYear (create blank fiscal year)
 */
class DepartmentController extends Controller
{
    // ── Validation rules (reused for store + update) ──────────
    private function rules(bool $requireYear = true): array
    {
        return [
            'year'            => $requireYear ? 'required|integer|min:2000|max:2099' : 'sometimes|integer',
            'department'      => 'required|string|max:200',
            'sheet_code'      => 'nullable|string|max:60',
            'parent_dept'     => 'nullable|string|max:200',
            'is_parent'       => 'boolean',
            'status'          => 'nullable|in:Approved,Pending,For Revision',
            'remarks'         => 'nullable|string|max:1000',
            'budget_fund_101' => 'nullable|numeric|min:0',
            'budget_fund_164' => 'nullable|numeric|min:0',
            'budget_fund_161' => 'nullable|numeric|min:0',
            'budget_fund_163' => 'nullable|numeric|min:0',
        ];
    }

    private function calcTotal(array $data): float
    {
        return round(
            (float)($data['budget_fund_101'] ?? 0) +
            (float)($data['budget_fund_164'] ?? 0) +
            (float)($data['budget_fund_161'] ?? 0) +
            (float)($data['budget_fund_163'] ?? 0),
        2);
    }

    // ── GET /departments ──────────────────────────────────────
    public function index()
    {
        $years = DB::table('wfp_submissions')
            ->select('year')
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year');

        $deptsByYear = [];
        foreach ($years as $yr) {
            $deptsByYear[$yr] = DB::table('wfp_submissions')
                ->where('year', $yr)
                ->orderByRaw("CASE WHEN parent_dept IS NULL THEN 0 ELSE 1 END")
                ->orderBy('no')
                ->get()
                ->map(fn($d) => [
                    'id'              => $d->id,
                    'no'              => $d->no,
                    'department'      => $d->department,
                    'sheet_code'      => $d->sheet_code,
                    'parent_dept'     => $d->parent_dept,
                    'is_parent'       => (bool)$d->is_parent,
                    'status'          => $d->status,
                    'remarks'         => $d->remarks,
                    'budget_total'    => (float)$d->budget_total,
                    'budget_fund_101' => (float)$d->budget_fund_101,
                    'budget_fund_164' => (float)$d->budget_fund_164,
                    'budget_fund_161' => (float)$d->budget_fund_161,
                    'budget_fund_163' => (float)$d->budget_fund_163,
                ])->toArray();
        }

        return Inertia::render('Departments', [
            'years'       => $years,
            'deptsByYear' => $deptsByYear,
        ]);
    }

    // ── POST /departments ─────────────────────────────────────
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), $this->rules(true));
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $data = $v->validated();
        $id   = DB::table('wfp_submissions')->insertGetId([
            'year'            => $data['year'],
            'no'              => $data['sheet_code'] ?? '',
            'department'      => trim($data['department']),
            'sheet_code'      => $data['sheet_code'] ?? '',
            'parent_dept'     => $data['parent_dept'] ?? null,
            'is_parent'       => $data['is_parent'] ?? false,
            'status'          => $data['status'] ?? 'Pending',
            'remarks'         => $data['remarks'] ?? '',
            'budget_total'    => $this->calcTotal($data),
            'budget_fund_101' => $data['budget_fund_101'] ?? 0,
            'budget_fund_164' => $data['budget_fund_164'] ?? 0,
            'budget_fund_161' => $data['budget_fund_161'] ?? 0,
            'budget_fund_163' => $data['budget_fund_163'] ?? 0,
            'pi_count'        => 0,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json(['success' => true, 'id' => $id,
            'message' => "Department '{$data['department']}' added successfully."]);
    }

    // ── PUT /departments/{id} ─────────────────────────────────
    public function update(Request $request, int $id)
    {
        $row = DB::table('wfp_submissions')->find($id);
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        $v = Validator::make($request->all(), $this->rules(false));
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $data = $v->validated();
        DB::table('wfp_submissions')->where('id', $id)->update([
            'department'      => trim($data['department'] ?? $row->department),
            'sheet_code'      => $data['sheet_code'] ?? $row->sheet_code,
            'parent_dept'     => array_key_exists('parent_dept', $data)
                                    ? ($data['parent_dept'] ?: null)
                                    : $row->parent_dept,
            'is_parent'       => $data['is_parent'] ?? $row->is_parent,
            'status'          => $data['status']    ?? $row->status,
            'remarks'         => $data['remarks']   ?? $row->remarks,
            'budget_total'    => $this->calcTotal(array_merge((array)$row, $data)),
            'budget_fund_101' => $data['budget_fund_101'] ?? $row->budget_fund_101,
            'budget_fund_164' => $data['budget_fund_164'] ?? $row->budget_fund_164,
            'budget_fund_161' => $data['budget_fund_161'] ?? $row->budget_fund_161,
            'budget_fund_163' => $data['budget_fund_163'] ?? $row->budget_fund_163,
            'updated_at'      => now(),
        ]);

        return response()->json(['success' => true,
            'message' => "Department updated successfully."]);
    }

    // ── DELETE /departments/{id} ──────────────────────────────
    public function destroy(int $id)
    {
        $row = DB::table('wfp_submissions')->find($id);
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        // Also delete children if this is a parent
        DB::table('wfp_submissions')
            ->where('year', $row->year)
            ->where('parent_dept', $row->department)
            ->delete();

        DB::table('wfp_submissions')->where('id', $id)->delete();

        return response()->json(['success' => true,
            'message' => "Deleted '{$row->department}' and its sub-offices."]);
    }

    // ── DELETE /departments/year/{year} ───────────────────────
    public function destroyYear(int $year)
    {
        $count = DB::table('wfp_submissions')->where('year', $year)->count();
        if ($count === 0) {
            return response()->json(['success' => false,
                'message' => "No data found for FY {$year}."], 404);
        }

        DB::table('wfp_submissions')->where('year', $year)->delete();

        return response()->json(['success' => true,
            'message' => "All {$count} records for FY {$year} deleted."]);
    }

    // ── POST /departments/year ────────────────────────────────
    // Creates a blank fiscal year by copying department names from
    // the most recent existing year (with zero budgets)
    public function storeYear(Request $request)
    {
        $v = Validator::make($request->all(), [
            'year'        => 'required|integer|min:2000|max:2099',
            'copy_from'   => 'nullable|integer|min:2000|max:2099',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $newYear  = (int)$request->year;
        $copyFrom = (int)($request->copy_from ?? 0);

        // Prevent duplicate year
        if (DB::table('wfp_submissions')->where('year', $newYear)->exists()) {
            return response()->json(['success' => false,
                'message' => "FY {$newYear} already exists. Delete it first or upload a new file."], 422);
        }

        if (!$copyFrom) {
            // Just create one blank placeholder row
            DB::table('wfp_submissions')->insert([
                'year' => $newYear, 'no' => '1',
                'department'   => "FY {$newYear} — Add departments via manual entry or upload",
                'sheet_code'   => '',
                'status'       => 'Pending',
                'budget_total' => 0,
                'budget_fund_101' => 0, 'budget_fund_164' => 0,
                'budget_fund_161' => 0, 'budget_fund_163' => 0,
                'pi_count'     => 0,
                'created_at'   => now(), 'updated_at' => now(),
            ]);

            return response()->json(['success' => true,
                'message' => "FY {$newYear} created. Add departments manually or upload a WFP file."]);
        }

        // Copy structure from copyFrom year (zero out all budgets)
        $source = DB::table('wfp_submissions')->where('year', $copyFrom)
            ->orderBy('id')->get();

        if ($source->isEmpty()) {
            return response()->json(['success' => false,
                'message' => "FY {$copyFrom} has no data to copy from."], 422);
        }

        $now  = now();
        $rows = $source->map(fn($r) => [
            'year'            => $newYear,
            'no'              => $r->no,
            'department'      => $r->department,
            'sheet_code'      => $r->sheet_code,
            'parent_dept'     => $r->parent_dept,
            'is_parent'       => $r->is_parent,
            'status'          => 'Pending',
            'remarks'         => '',
            'budget_total'    => 0,
            'budget_fund_101' => 0,
            'budget_fund_164' => 0,
            'budget_fund_161' => 0,
            'budget_fund_163' => 0,
            'pi_count'        => 0,
            'created_at'      => $now,
            'updated_at'      => $now,
        ])->toArray();

        // Insert in chunks to avoid query size limits
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('wfp_submissions')->insert($chunk);
        }

        return response()->json(['success' => true,
            'message' => "FY {$newYear} created with {$source->count()} departments copied from FY {$copyFrom} (budgets set to ₱0)."]);
    }
}
