<?php

namespace App\Services\Wfp;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Keeps spreadsheet parsing lightweight by reading only the columns/rows used by WFP imports.
 */
class WfpReadFilter implements IReadFilter
{
    private const ALLOWED_COLUMNS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
    private const MAX_ROWS = 700;

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return in_array($columnAddress, self::ALLOWED_COLUMNS, true) && $row <= self::MAX_ROWS;
    }
}
