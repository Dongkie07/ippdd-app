<?php

namespace App\Services\Wfp;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WfpBudgetExtractor
{
    private const MAX_ROWS = 700;
    private const BUDGET_COLUMNS = [
        'fund_101' => 'I',
        'fund_164' => 'J',
        'fund_161' => 'K',
        'fund_163' => 'L',
        'budget'   => 'M',
    ];

    public function __construct(private readonly WfpAmountParser $amountParser)
    {
    }

    public function extract(Worksheet $worksheet, int $year): array
    {
        $rows = [];
        $started = false;
        $currentParent = null;
        $highestRow = min((int) $worksheet->getHighestRow(), self::MAX_ROWS);

        for ($rowNumber = 1; $rowNumber <= $highestRow; $rowNumber++) {
            $noColumn = strtoupper(trim((string) $worksheet->getCell("C{$rowNumber}")->getValue()));

            if (!$started) {
                $started = str_contains($noColumn, 'NO.') || $noColumn === 'NO';
                continue;
            }

            $row = $this->readRow($worksheet, $rowNumber, $year, $currentParent);

            if (!$row) {
                continue;
            }

            if ($row['is_parent']) {
                $currentParent = $row['department'];
                $row['parent_dept'] = null;
            }

            $rows[$this->uniqueKey($rows, $row['department'], $row['parent_dept'])] = $row;
        }

        return $rows;
    }

    private function readRow(Worksheet $worksheet, int $rowNumber, int $year, ?string $currentParent): ?array
    {
        $no = trim((string) $worksheet->getCell("C{$rowNumber}")->getValue());
        $department = trim((string) $worksheet->getCell("D{$rowNumber}")->getValue());
        $status = trim((string) $worksheet->getCell("E{$rowNumber}")->getValue());
        $remarks = trim((string) $worksheet->getCell("F{$rowNumber}")->getValue());

        if ($this->isInvalidDepartmentName($department)) {
            return null;
        }

        $amounts = $this->readAmounts($worksheet, $rowNumber);
        $isParent = (bool) preg_match('/^\d+$/', $no);
        $isChild = (bool) preg_match('/^[a-z]$/i', $no) && !$isParent;

        if ($amounts['budget'] <= 0 && !$isParent && !$isChild) {
            return null;
        }

        return [
            'no'          => $no,
            'department'  => $department,
            'sheet_code'  => $no,
            'year'        => $year,
            'status'      => $status ?: 'Pending',
            'remarks'     => $this->cleanRemarks($remarks),
            'fund_101'    => $amounts['fund_101'],
            'fund_164'    => $amounts['fund_164'],
            'fund_161'    => $amounts['fund_161'],
            'fund_163'    => $amounts['fund_163'],
            'budget'      => $amounts['budget'],
            'parent_dept' => $isChild ? $currentParent : null,
            'is_parent'   => $isParent,
            'pis'         => [],
        ];
    }

    private function readAmounts(Worksheet $worksheet, int $rowNumber): array
    {
        $amounts = [];

        foreach (self::BUDGET_COLUMNS as $field => $column) {
            $amounts[$field] = $this->amountParser->parse($worksheet->getCell("{$column}{$rowNumber}")->getCalculatedValue());
        }

        return $amounts;
    }

    private function uniqueKey(array $rows, string $department, ?string $parentDepartment): string
    {
        $baseKey = strtoupper(trim($department));
        $key = $baseKey;
        $suffix = 0;

        while (isset($rows[$key]) && $rows[$key]['parent_dept'] !== $parentDepartment) {
            $suffix++;
            $key = "{$baseKey}_{$suffix}";
        }

        return $key;
    }

    private function isInvalidDepartmentName(string $department): bool
    {
        return $department === '' || $department === 'nan' || strlen($department) > 80;
    }

    private function cleanRemarks(string $remarks): string
    {
        return strlen($remarks) > 2 && $remarks !== 'nan' ? $remarks : '';
    }
}
