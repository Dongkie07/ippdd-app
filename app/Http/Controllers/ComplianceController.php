<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ComplianceController extends Controller
{
    // ── GET /compliance ───────────────────────────────────────
    public function index()
    {
        $submissions = DB::table('wfp_submissions')
            ->orderBy('year')
            ->orderByRaw("CAST(CASE WHEN no REGEXP '^[0-9]+$' THEN no ELSE '999' END AS UNSIGNED)")
            ->orderBy('no')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'year'             => $s->year,
                'no'               => $s->no,
                'department'       => $s->department,
                'sheet_code'       => $s->sheet_code,
                'status'           => $s->status,
                'remarks'          => $s->remarks,
                'budget_total'     => (float) $s->budget_total,
                'budget_fund_101'  => (float) $s->budget_fund_101,
                'budget_fund_164'  => (float) $s->budget_fund_164,
                'budget_fund_161'  => (float) $s->budget_fund_161,
                'budget_fund_163'  => (float) $s->budget_fund_163,
                'pi_count'         => (int) $s->pi_count,
            ]);

        $years = DB::table('wfp_submissions')
            ->distinct()
            ->orderBy('year')
            ->pluck('year')
            ->map(fn($y) => (int) $y)
            ->toArray();

        // Load PI samples for the latest year's departments upfront
        // (saves an extra API call on first load)
        $latestYear = end($years) ?: date('Y');
        $topDepts   = DB::table('wfp_submissions')
            ->where('year', $latestYear)
            ->where('pi_count', '>', 0)
            ->orderByDesc('pi_count')
            ->limit(5)
            ->pluck('id');

        $piSamples = [];
        foreach ($topDepts as $subId) {
            $piSamples[$subId] = DB::table('wfp_pis')
                ->where('submission_id', $subId)
                ->orderBy('seq')
                ->limit(20)
                ->get()
                ->map(fn($p) => [
                    'id'               => $p->id,
                    'seq'              => $p->seq,
                    'code'             => $p->code,
                    'reference_source' => $p->reference_source,
                    'description'      => $p->description,
                    'definition'       => $p->definition,
                    'target'           => $p->target,
                ])->toArray();
        }

        return Inertia::render('Compliance', [
            'submissions' => $submissions,
            'years'       => empty($years) ? [date('Y')] : $years,
            'piSamples'   => $piSamples,
        ]);
    }

    // ── GET /compliance/pis/{submissionId} ────────────────────
    // Called by the frontend when user expands a department row
    public function pis(int $submissionId)
    {
        $pis = DB::table('wfp_pis')
            ->where('submission_id', $submissionId)
            ->orderBy('seq')
            ->get()
            ->map(fn($p) => [
                'id'               => $p->id,
                'seq'              => $p->seq,
                'code'             => $p->code,
                'reference_source' => $p->reference_source,
                'description'      => $p->description,
                'definition'       => $p->definition,
                'target'           => $p->target,
            ]);

        return response()->json($pis);
    }
}
