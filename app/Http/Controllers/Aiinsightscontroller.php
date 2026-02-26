<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiInsightsController extends Controller
{
    public function analyze(Request $request): StreamedResponse
    {
        $year  = (int) $request->get('year', 2026);
        $table = 'wfp_submissions';

        $departments = DB::table($table)->where('year', $year)->orderByDesc('budget_total')->get()
            ->map(fn($d) => [
                'department' => $d->department,
                'budget'     => round((float) $d->budget_total,    2),
                'fund_101'   => round((float) $d->budget_fund_101, 2),
                'fund_164'   => round((float) $d->budget_fund_164, 2),
                'fund_161'   => round((float) $d->budget_fund_161, 2),
                'fund_163'   => round((float) $d->budget_fund_163, 2),
            ])->toArray();

        $yearTrends = DB::table($table)
            ->select('year',
                DB::raw('SUM(budget_total)    as total_budget'),
                DB::raw('COUNT(*)             as dept_count'),
                DB::raw('SUM(budget_fund_101) as total_101'),
                DB::raw('SUM(budget_fund_164) as total_164'))
            ->groupBy('year')->orderBy('year')->get()
            ->map(fn($y) => [
                'year'         => $y->year,
                'total_budget' => round((float) $y->total_budget, 2),
                'dept_count'   => (int) $y->dept_count,
                'fund_101'     => round((float) $y->total_101, 2),
                'fund_164'     => round((float) $y->total_164, 2),
            ])->toArray();

        $prevBudgets = DB::table($table)->where('year', $year - 1)->pluck('budget_total', 'department');
        $yoy = [];
        foreach ($departments as $d) {
            $prev = $prevBudgets->get($d['department']);
            if ($prev && (float)$prev > 0) {
                $yoy[] = [
                    'department' => $d['department'],
                    'current'    => $d['budget'],
                    'previous'   => round((float) $prev, 2),
                    'change_pct' => round(($d['budget'] - $prev) / $prev * 100, 1),
                ];
            }
        }
        usort($yoy, fn($a, $b) => abs($b['change_pct']) <=> abs($a['change_pct']));

        $totalBudget = array_sum(array_column($departments, 'budget'));
        $totalF101   = array_sum(array_column($departments, 'fund_101'));
        $totalF164   = array_sum(array_column($departments, 'fund_164'));

        $payload = json_encode([
            'fiscal_year'        => $year,
            'total_budget'       => $totalBudget,
            'total_budget_m'     => round($totalBudget / 1e6, 3),
            'total_departments'  => count($departments),
            'fund_101_total'     => $totalF101,
            'fund_164_total'     => $totalF164,
            'fund_101_pct'       => $totalBudget > 0 ? round($totalF101 / $totalBudget * 100, 1) : 0,
            'fund_164_pct'       => $totalBudget > 0 ? round($totalF164 / $totalBudget * 100, 1) : 0,
            'top_10_departments' => array_slice($departments, 0, 10),
            'year_trends'        => $yearTrends,
            'top_yoy_changes'    => array_slice($yoy, 0, 8),
        ], JSON_PRETTY_PRINT);

        $prevYear = $year - 1;
        $prompt = <<<PROMPT
You are a senior financial analyst advising the executive leadership of Davao del Norte State College (DNSC), a Philippine state university.

Analyze the FY {$year} Work & Financial Plan (WFP) budget data and write a concise executive briefing with exactly 4 sections. Be specific — cite real ₱ figures and department names from the data. Write for a College President and Board of Trustees audience.

## 1. Budget Overview
- Total FY {$year} allocation and how it compares to prior years
- Split between Fund 101 (GAA/government subsidy) vs Fund 164 (Fiduciary/internally generated income)
- What the fund mix means for DNSC's financial resilience

## 2. Top Department Allocations
- Which 3–4 departments received the largest allocations and their share of total budget
- Whether concentration is proportionate to institutional priorities
- Any imbalance worth flagging to leadership

## 3. Year-over-Year Changes vs FY {$prevYear}
- Departments with largest increases and decreases
- Whether the overall budget trend is positive or a cause for concern
- Any sudden spikes or drops that need explanation

## 4. Recommendations
Exactly 3 specific, actionable recommendations DNSC leadership can act on this fiscal year. Each must name a specific department or fund and suggest a concrete next step.

**Rules:** Bold key numbers and department names. 3–5 bullets per section max. No mention of Performance Indicators. Use ₱ for all amounts.

DATA:
{$payload}
PROMPT;

        $apiKey = config('services.gemini.key');
        $model  = 'gemini-2.0-flash';
        $url    = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:streamGenerateContent?alt=sse&key={$apiKey}";

        $body = json_encode([
            'system_instruction' => ['parts' => [['text' =>
                'You are a senior financial analyst for Philippine state universities. Be direct, cite real figures, and write for executives. Use ₱ for peso amounts.'
            ]]],
            'contents'         => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 2048],
        ]);

        return new StreamedResponse(function () use ($url, $body) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_TIMEOUT        => 90,
                CURLOPT_WRITEFUNCTION  => function ($ch, $data) {
                    foreach (explode("\n", $data) as $line) {
                        $line = trim($line);
                        if (!str_starts_with($line, 'data: ')) continue;
                        $json = substr($line, 6);
                        if ($json === '[DONE]') { echo "data: [DONE]\n\n"; ob_flush(); flush(); continue; }
                        $decoded = json_decode($json, true);
                        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
                        if ($text !== null) { echo 'data: '.json_encode(['text'=>$text])."\n\n"; ob_flush(); flush(); }
                    }
                    return strlen($data);
                },
            ]);
            curl_exec($ch);
            echo "data: [DONE]\n\n"; ob_flush(); flush();
            curl_close($ch);
        }, 200, ['Content-Type'=>'text/event-stream','Cache-Control'=>'no-cache','X-Accel-Buffering'=>'no']);
    }
}