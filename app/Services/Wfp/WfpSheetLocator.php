<?php

namespace App\Services\Wfp;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class WfpSheetLocator
{
    private const PREVIEW_ROWS = 80;

    public function statusSheetName(Spreadsheet $spreadsheet): string
    {
        $sheetNames = $spreadsheet->getSheetNames();

        foreach ($sheetNames as $sheetName) {
            $normalizedName = strtoupper(trim($sheetName));

            if (!$this->looksLikeStatusSheetName($normalizedName)) {
                continue;
            }

            $worksheet = $spreadsheet->getSheetByName($sheetName);

            if ($this->containsStatusSheetMarkers($this->sheetText($worksheet, self::PREVIEW_ROWS))) {
                return $sheetName;
            }
        }

        throw new RuntimeException($this->statusSheetNotFoundMessage($sheetNames));
    }

    public function wfpSheetName(Spreadsheet $spreadsheet, int $year): ?string
    {
        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            if (str_contains(strtoupper(trim($sheetName)), "WFP {$year}")) {
                return $sheetName;
            }
        }

        return null;
    }

    private function looksLikeStatusSheetName(string $name): bool
    {
        return str_contains($name, 'STATUS')
            || str_contains($name, 'MONITORING')
            || str_contains($name, 'GUIDELINE');
    }

    private function containsStatusSheetMarkers(string $sheetText): bool
    {
        return str_contains($sheetText, 'FUND 101')
            || str_contains($sheetText, 'STATUS OF COMPLIANCE')
            || str_contains($sheetText, 'BUDGET ALLOCATION');
    }

    private function sheetText(?Worksheet $worksheet, int $maxRows): string
    {
        if (!$worksheet) {
            return '';
        }

        $text = '';

        for ($row = 1; $row <= $maxRows; $row++) {
            foreach (range('A', 'M') as $column) {
                $text .= ' ' . strtoupper((string) $worksheet->getCell("{$column}{$row}")->getValue());
            }
        }

        return $text;
    }

    private function statusSheetNotFoundMessage(array $sheetNames): string
    {
        $sheetList = implode(', ', $sheetNames);

        return "Could not find the STATUS AND MONITORING sheet. "
            . "Please make sure your file has a sheet named 'STATUS AND MONITORING'. "
            . "Sheets found: {$sheetList}";
    }
}
