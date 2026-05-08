<?php

namespace App\Http\Controllers;

use App\Services\Wfp\WfpImportPersister;
use App\Services\Wfp\WfpSpreadsheetParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WfpImportController extends Controller
{
    public function index()
    {
        return Inertia::render('Upload', [
            'history' => DB::table('wfp_submissions')
                ->select(
                    'year',
                    DB::raw('COUNT(*) as dept_count'),
                    DB::raw('SUM(budget_total) as total_budget'),
                    DB::raw('MAX(created_at) as created_at'),
                )
                ->groupBy('year')
                ->orderByDesc('year')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'filename'     => "WFP_{$row->year}.xlsx",
                    'year'         => $row->year,
                    'dept_count'   => $row->dept_count,
                    'total_budget' => $row->total_budget,
                    'created_at'   => $row->created_at,
                ]),
        ]);
    }

    public function parse(Request $request, WfpSpreadsheetParser $parser)
    {
        $this->allowLargeSpreadsheetUpload();

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:30720',
            'year' => 'required|integer|min:2020|max:2035',
        ]);

        $file = $request->file('file');

        try {
            $parsed = $parser->parse($file->getPathname(), (int) $validated['year']);

            return response()->json([
                'success'  => true,
                'filename' => $file->getClientOriginalName(),
                ...$parsed,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'error'   => 'Parse error: ' . $exception->getMessage(),
            ], 422);
        }
    }

    public function confirm(Request $request, WfpImportPersister $persister)
    {
        $validated = $request->validate([
            'rows'     => 'required|array|min:1',
            'filename' => 'required|string',
        ]);

        try {
            $summary = $persister->save($validated['rows'], $validated['filename']);

            return response()->json([
                'success' => true,
                ...$summary,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'error'   => 'DB error: ' . $exception->getMessage(),
            ], 500);
        }
    }

    private function allowLargeSpreadsheetUpload(): void
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');
    }
}
