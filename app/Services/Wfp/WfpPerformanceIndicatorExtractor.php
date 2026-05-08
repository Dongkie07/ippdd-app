<?php

namespace App\Services\Wfp;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WfpPerformanceIndicatorExtractor
{
    private const MAX_ROWS = 700;

    public function attachToBudgetRows(Worksheet $worksheet, array $budgetRows): array
    {
        $highestRow = min((int) $worksheet->getHighestRow(), self::MAX_ROWS);
        $currentDepartment = null;
        $sequence = 0;

        for ($rowNumber = 8; $rowNumber <= $highestRow; $rowNumber++) {
            $cells = $this->readCells($worksheet, $rowNumber);

            if ($this->isSectionEnd($cells['d'])) {
                break;
            }

            if ($this->isDepartmentHeader($cells)) {
                $currentDepartment = strtoupper(trim($cells['b'] ?: $cells['a']));
                $sequence = 0;
                continue;
            }

            if (!$this->isIndicatorRow($currentDepartment, $cells['d'])) {
                continue;
            }

            $matchedDepartment = $this->matchDepartment($currentDepartment, $budgetRows);

            if (!$matchedDepartment) {
                continue;
            }

            $sequence++;
            $budgetRows[$matchedDepartment]['pis'][] = $this->makeIndicator($cells, $sequence);
        }

        return $budgetRows;
    }

    private function readCells(Worksheet $worksheet, int $rowNumber): array
    {
        return [
            'a' => trim((string) $worksheet->getCell("A{$rowNumber}")->getValue()),
            'b' => trim((string) $worksheet->getCell("B{$rowNumber}")->getValue()),
            'c' => trim((string) $worksheet->getCell("C{$rowNumber}")->getValue()),
            'd' => trim((string) $worksheet->getCell("D{$rowNumber}")->getValue()),
            'e' => trim((string) $worksheet->getCell("E{$rowNumber}")->getValue()),
            'f' => trim((string) $worksheet->getCell("F{$rowNumber}")->getValue()),
        ];
    }

    private function isSectionEnd(string $indicatorText): bool
    {
        $text = strtoupper($indicatorText);

        return str_contains($text, 'PREPARED BY') || str_contains($text, 'GRAND TOTAL');
    }

    private function isDepartmentHeader(array $cells): bool
    {
        $bLooksLikeHeader = $cells['b'] !== ''
            && $cells['d'] === ''
            && strlen($cells['b']) > 4
            && !preg_match('/^[\d\.]+$/', $cells['b'])
            && !preg_match('/^(GAA|OPCR|SP[-\s]|CF\d|A-PAP|ACAD)/i', $cells['b']);

        $aLooksLikeHeader = $cells['a'] !== ''
            && $cells['b'] === ''
            && $cells['d'] === ''
            && strlen($cells['a']) > 4
            && !preg_match('/^(AREA|1$)/', strtoupper($cells['a']));

        return $bLooksLikeHeader || $aLooksLikeHeader;
    }

    private function isIndicatorRow(?string $currentDepartment, string $indicatorText): bool
    {
        if (!$currentDepartment || $indicatorText === '' || strlen($indicatorText) <= 5) {
            return false;
        }

        return !in_array(strtoupper($indicatorText), [
            'PERFORMANCE INDICATORS (PI)',
            'PERFORMANCE INDICATORS',
            '4',
        ], true);
    }

    private function makeIndicator(array $cells, int $sequence): array
    {
        return [
            'seq'         => $sequence,
            'code'        => substr($cells['b'], 0, 60),
            'reference'   => substr($cells['c'], 0, 100),
            'description' => substr($cells['d'], 0, 250),
            'definition'  => substr($cells['e'], 0, 250),
            'target'      => substr($cells['f'], 0, 100),
        ];
    }

    private function matchDepartment(string $needle, array $budgetRows): ?string
    {
        $normalizedNeedle = strtoupper(trim($needle));

        if (isset($budgetRows[$normalizedNeedle])) {
            return $normalizedNeedle;
        }

        $words = array_filter(explode(' ', $normalizedNeedle), fn (string $word) => strlen($word) > 3);
        $bestMatch = null;
        $bestScore = 0;

        foreach (array_keys($budgetRows) as $key) {
            $score = 0;

            foreach ($words as $word) {
                if (str_contains($key, $word)) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $key;
            }
        }

        return $bestScore >= 1 ? $bestMatch : null;
    }
}
