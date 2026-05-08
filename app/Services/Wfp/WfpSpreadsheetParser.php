<?php

namespace App\Services\Wfp;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Coordinates WFP parsing. The detailed sheet lookup, budget extraction,
 * and PI extraction live in focused classes so debugging is not an archaeology project.
 */
class WfpSpreadsheetParser
{
    public function __construct(
        private readonly WfpSheetLocator $sheetLocator,
        private readonly WfpBudgetExtractor $budgetExtractor,
        private readonly WfpPerformanceIndicatorExtractor $indicatorExtractor,
    ) {
    }

    public function parse(string $path, int $year): array
    {
        $spreadsheet = $this->loadSpreadsheet($path);
        $statusSheetName = $this->sheetLocator->statusSheetName($spreadsheet);
        $wfpSheetName = $this->sheetLocator->wfpSheetName($spreadsheet, $year);

        $rows = $this->budgetExtractor->extract(
            $spreadsheet->getSheetByName($statusSheetName),
            $year,
        );

        if ($wfpSheetName) {
            $rows = $this->indicatorExtractor->attachToBudgetRows(
                $spreadsheet->getSheetByName($wfpSheetName),
                $rows,
            );
        }

        $preview = array_values(array_map(
            fn (array $row) => array_merge($row, ['selected' => true]),
            $rows,
        ));

        return [
            'preview'      => $preview,
            'year'         => $year,
            'total_budget' => array_sum(array_column($preview, 'budget')),
            'total_depts'  => count($preview),
            'total_pis'    => array_sum(array_map(fn (array $row) => count($row['pis'] ?? []), $preview)),
            'status_sheet' => $statusSheetName,
            'wfp_sheet'    => $wfpSheetName,
        ];
    }

    private function loadSpreadsheet(string $path): Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new WfpReadFilter());

        return $reader->load($path);
    }
}
