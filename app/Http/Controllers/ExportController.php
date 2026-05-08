<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private const HEADERS = [
        'Year',
        'No.',
        'Department',
        'Parent Department',
        'Status',
        'Remarks',
        'Total Budget',
        'Fund 101',
        'Fund 164',
        'Fund 161',
        'Fund 163',
        'PI Count',
    ];

    public function csv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::HEADERS);

            foreach ($this->rows() as $row) {
                fputcsv($handle, $this->formatRow($row));
            }

            fclose($handle);
        }, 'wfp-export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function excel(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('WFP Export');
            $sheet->fromArray(self::HEADERS, null, 'A1');

            $rowNumber = 2;
            foreach ($this->rows() as $row) {
                $sheet->fromArray($this->formatRow($row), null, "A{$rowNumber}");
                $rowNumber++;
            }

            foreach (range('A', 'L') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            (new Xlsx($spreadsheet))->save('php://output');
        }, 'wfp-export.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function rows()
    {
        return DB::table('wfp_submissions')
            ->orderByDesc('year')
            ->orderBy('parent_dept')
            ->orderBy('department')
            ->get();
    }

    private function formatRow(object $row): array
    {
        return [
            $row->year,
            $row->no,
            $row->department,
            $row->parent_dept,
            $row->status,
            $row->remarks,
            (float) $row->budget_total,
            (float) $row->budget_fund_101,
            (float) $row->budget_fund_164,
            (float) $row->budget_fund_161,
            (float) $row->budget_fund_163,
            (int) $row->pi_count,
        ];
    }
}
