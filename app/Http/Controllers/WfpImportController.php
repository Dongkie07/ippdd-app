<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

// ─── Memory-saving read filter ────────────────────────────────
class WfpReadFilter implements IReadFilter
{
    public function readCell(string $col, int $row, string $sheet = ''): bool
    {
        return in_array($col, ['A','B','C','D','E','F','G','H','I','J','K','L','M'])
            && $row <= 700;
    }
}

class WfpImportController extends Controller
{
    // ── Upload page ───────────────────────────────────────────
    public function index()
    {
        return Inertia::render('Upload', [
            'history' => DB::table('wfp_imports')->orderByDesc('created_at')->limit(10)->get(),
        ]);
    }

    // ── POST /upload/parse ────────────────────────────────────
    public function parse(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:30720',
            'year' => 'required|integer|min:2020|max:2035',
        ]);

        $year = (int) $request->year;
        $path = $request->file('file')->getPathname();
        $name = $request->file('file')->getClientOriginalName();

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $reader->setReadFilter(new WfpReadFilter());
            $spreadsheet = $reader->load($path);
            $sheetNames  = $spreadsheet->getSheetNames();

            // ── Locate STATUS sheet and WFP sheet ────────────
            $statusSheet = null;
            $wfpSheet    = null;

            foreach ($sheetNames as $sn) {
                $su = strtoupper($sn);
                if ($statusSheet === null &&
                    (str_contains($su,'STATUS') || str_contains($su,'MONITORING') || str_contains($su,'GUIDELINE'))) {
                    $ws   = $spreadsheet->getSheetByName($sn);
                    $peek = $this->sheetText($ws, 50);
                    if (str_contains($peek,'FUND 101') || str_contains($peek,'STATUS OF COMPLIANCE')) {
                        $statusSheet = $sn;
                    }
                }
                if ($wfpSheet === null && str_contains($su, "WFP {$year}")) {
                    $wfpSheet = $sn;
                }
            }

            if (!$statusSheet) {
                return response()->json(['success'=>false,
                    'error'=>"No STATUS/MONITORING sheet found. Sheets: ".implode(', ',$sheetNames)], 422);
            }
            if (!$wfpSheet) {
                return response()->json(['success'=>false,
                    'error'=>"No 'WFP {$year}' sheet found. Sheets: ".implode(', ',$sheetNames)], 422);
            }

            // ── Extract data ──────────────────────────────────
            $data = $this->extractBudgets($spreadsheet->getSheetByName($statusSheet), $year);
            $data = $this->extractPIs($spreadsheet->getSheetByName($wfpSheet), $data);

            $preview = array_values(array_map(fn($d) => array_merge($d, ['selected'=>true]), $data));

            return response()->json([
                'success'      => true,
                'preview'      => $preview,
                'year'         => $year,
                'filename'     => $name,
                'total_budget' => array_sum(array_column($preview, 'budget')),
                'total_depts'  => count($preview),
                'total_pis'    => array_sum(array_map(fn($d) => count($d['pis']), $preview)),
                'status_sheet' => $statusSheet,
                'wfp_sheet'    => $wfpSheet,
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success'=>false, 'error'=>'Parse error: '.$e->getMessage()], 422);
        }
    }

    // ── POST /upload/confirm ──────────────────────────────────
    public function confirm(Request $request)
    {
        $request->validate(['rows'=>'required|array|min:1','filename'=>'required|string']);

        $rows     = $request->rows;
        $filename = $request->filename;
        $year     = (int)($rows[0]['year'] ?? 0);
        $now      = now();

        DB::beginTransaction();
        try {
            // Wipe existing year data
            $ids = DB::table('wfp_submissions')->where('year',$year)->pluck('id');
            DB::table('wfp_pis')->whereIn('submission_id',$ids)->delete();
            DB::table('wfp_submissions')->where('year',$year)->delete();

            $deptCount = 0;
            $piCount   = 0;
            $totalBudget = 0;

            foreach ($rows as $row) {
                if (!($row['selected'] ?? true)) continue;

                $pis   = $row['pis'] ?? [];
                $subId = DB::table('wfp_submissions')->insertGetId([
                    'year'            => $year,
                    'no'              => $row['no'] ?? null,
                    'department'      => $row['department'],
                    'sheet_code'      => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/','', $row['department']),0,20)),
                    'status'          => $row['status'] ?? 'Pending',
                    'remarks'         => $row['remarks'] ?? null,
                    'budget_total'    => (float)($row['budget']   ?? 0),
                    'budget_fund_101' => (float)($row['fund_101'] ?? 0),
                    'budget_fund_164' => (float)($row['fund_164'] ?? 0),
                    'budget_fund_161' => (float)($row['fund_161'] ?? 0),
                    'budget_fund_163' => (float)($row['fund_163'] ?? 0),
                    'pi_count'        => count($pis),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);

                foreach ($pis as $pi) {
                    DB::table('wfp_pis')->insert([
                        'submission_id'    => $subId,
                        'year'             => $year,
                        'department'       => $row['department'],
                        'seq'              => $pi['seq']         ?? 0,
                        'code'             => $pi['code']        ?? null,
                        'reference_source' => $pi['reference']   ?? null,
                        'description'      => $pi['description'] ?? '',
                        'definition'       => $pi['definition']  ?? null,
                        'target'           => $pi['target']      ?? null,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                }

                $deptCount++;
                $piCount    += count($pis);
                $totalBudget += (float)($row['budget'] ?? 0);
            }

            // Log import
            if (Schema::hasTable('wfp_imports')) {
                DB::table('wfp_imports')->insert([
                    'filename'     => $filename,
                    'year'         => $year,
                    'dept_count'   => $deptCount,
                    'total_budget' => $totalBudget,
                    'imported_by'  => 'Admin',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }

            DB::commit();
            return response()->json(['success'=>true,
                'message'=>"FY {$year} saved — {$deptCount} departments, {$piCount} PIs."]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'error'=>'DB error: '.$e->getMessage()],500);
        }
    }

    // ── Extract budgets from STATUS AND MONITORING sheet ──────
    private function extractBudgets($ws, int $year): array
    {
        $results     = [];
        $started     = false;
        $highestRow  = min((int)$ws->getHighestRow(), 700);

        for ($r = 1; $r <= $highestRow; $r++) {
            $c3 = strtoupper(trim((string)$ws->getCell('C'.$r)->getValue()));

            if (!$started) {
                if (str_contains($c3,'NO.') || $c3 === 'NO') { $started = true; }
                continue;
            }

            $no      = trim((string)$ws->getCell('C'.$r)->getValue());
            $dept    = trim((string)$ws->getCell('D'.$r)->getValue());
            $status  = trim((string)$ws->getCell('E'.$r)->getValue());
            $remarks = trim((string)$ws->getCell('F'.$r)->getValue());

            // Column layout (0-indexed from A):
            // G = Total Budget in WFP  H = Tuition  I = Fund101  J = Fund164  K = Fund161  L = Fund163  M = TOTAL
            $total = $this->num($ws->getCell('M'.$r)->getCalculatedValue());

            if (empty($dept) || $dept === 'nan' || $total <= 0) continue;

            $key = strtoupper(trim($dept));
            $results[$key] = [
                'no'         => $no,
                'department' => $dept,
                'year'       => $year,
                'status'     => $status ?: 'Pending',
                'remarks'    => (strlen($remarks) > 2 && $remarks !== 'nan') ? $remarks : '',
                'fund_101'   => $this->num($ws->getCell('I'.$r)->getCalculatedValue()),
                'fund_164'   => $this->num($ws->getCell('J'.$r)->getCalculatedValue()),
                'fund_161'   => $this->num($ws->getCell('K'.$r)->getCalculatedValue()),
                'fund_163'   => $this->num($ws->getCell('L'.$r)->getCalculatedValue()),
                'budget'     => $total,
                'pis'        => [],
            ];
        }

        return $results;
    }

    // ── Extract PIs from WFP consolidated sheet ───────────────
    private function extractPIs($ws, array $data): array
    {
        $highestRow  = min((int)$ws->getHighestRow(), 700);
        $currentDept = null;
        $seq         = 0;

        for ($r = 8; $r <= $highestRow; $r++) {
            $a = trim((string)$ws->getCell('A'.$r)->getValue());
            $b = trim((string)$ws->getCell('B'.$r)->getValue());
            $c = trim((string)$ws->getCell('C'.$r)->getValue());
            $d = trim((string)$ws->getCell('D'.$r)->getValue());
            $e = trim((string)$ws->getCell('E'.$r)->getValue());
            $f = trim((string)$ws->getCell('F'.$r)->getValue());

            if (str_contains(strtoupper($d),'PREPARED BY')) break;
            if (str_contains(strtoupper($d),'GRAND TOTAL'))  break;

            // ── Department header detection ───────────────────
            // Pattern: colB has dept name AND colD is empty
            $isDeptHeader = !empty($b)
                && empty($d)
                && strlen($b) > 4
                && !preg_match('/^[\d\.]+$/', $b)
                && !preg_match('/^(GAA|OPCR|SP[-\s]|CF\d|A-PAP|ACAD)/i', $b);

            // Also catch when colA has the dept name
            if (!empty($a) && empty($b) && empty($d) && strlen($a) > 4
                && !preg_match('/^(AREA|1$)/', strtoupper($a))) {
                $isDeptHeader = true;
                $b = $a;
            }

            if ($isDeptHeader) {
                $currentDept = strtoupper(trim($b));
                $seq = 0;
                continue;
            }

            // ── PI row ────────────────────────────────────────
            if ($currentDept && !empty($d) && strlen($d) > 5) {
                if (in_array(strtoupper($d), ['PERFORMANCE INDICATORS (PI)','PERFORMANCE INDICATORS','4'])) continue;

                $matched = $this->matchDept($currentDept, $data);
                if ($matched) {
                    $seq++;
                    $data[$matched]['pis'][] = [
                        'seq'         => $seq,
                        'code'        => substr($b, 0, 60),
                        'reference'   => substr($c, 0, 100),
                        'description' => substr($d, 0, 250),
                        'definition'  => substr($e, 0, 250),
                        'target'      => substr($f, 0, 100),
                    ];
                }
            }
        }

        return $data;
    }

    // ── Fuzzy dept matcher ────────────────────────────────────
    private function matchDept(string $needle, array $data): ?string
    {
        $n = strtoupper(trim($needle));
        if (isset($data[$n])) return $n;

        $words = array_filter(explode(' ', $n), fn($w) => strlen($w) > 3);
        $best  = null; $score = 0;

        foreach (array_keys($data) as $key) {
            $s = 0;
            foreach ($words as $w) { if (str_contains($key, $w)) $s++; }
            if ($s > $score) { $score = $s; $best = $key; }
        }

        return $score >= 1 ? $best : null;
    }

    // ── Helpers ───────────────────────────────────────────────
    private function num(mixed $v): float
    {
        if ($v === null || $v === '') return 0.0;
        $s = preg_replace('/[₱,\s\x{00A0}]/u', '', (string)$v);
        $s = trim(explode("\n", $s)[0]);
        $n = filter_var($s, FILTER_VALIDATE_FLOAT);
        return ($n !== false && $n >= 0) ? round((float)$n, 2) : 0.0;
    }

    private function sheetText($ws, int $maxRows = 50): string
    {
        $t = '';
        for ($r = 1; $r <= $maxRows; $r++)
            for ($c = 'A'; $c <= 'M'; $c++)
                $t .= ' '.strtoupper((string)$ws->getCell($c.$r)->getValue());
        return $t;
    }
}