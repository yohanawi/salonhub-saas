<?php

namespace App\Services\Reports;

use App\Support\Reports\ReportDocument;
use App\Support\Reports\ReportTable;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns any ReportDocument into CSV, Excel (.xlsx, one sheet per table) or PDF.
 */
class ReportExporter
{
    public const FORMATS = ['csv', 'xlsx', 'pdf'];

    private const MIME = [
        'csv' => 'text/csv; charset=UTF-8',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pdf' => 'application/pdf',
    ];

    public function download(ReportDocument $document, string $format): Response
    {
        abort_unless(in_array($format, self::FORMATS, true), 404);

        return response($this->render($document, $format), 200, [
            'Content-Type' => self::MIME[$format],
            'Content-Disposition' => 'attachment; filename="' . $document->filename($format) . '"',
        ]);
    }

    public function mime(string $format): string
    {
        return self::MIME[$format];
    }

    public function render(ReportDocument $document, string $format): string
    {
        return match ($format) {
            'xlsx' => $this->xlsx($document),
            'pdf' => $this->pdf($document),
            default => $this->csv($document),
        };
    }

    private function csv(ReportDocument $document): string
    {
        $handle = fopen('php://temp', 'r+');
        // Excel needs the BOM to read UTF-8 (Sinhala, Tamil) correctly.
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [$document->title . ' · ' . $document->salonName]);

        foreach ($document->meta as $label => $value) {
            fputcsv($handle, [$label, $value]);
        }

        foreach ($document->summary as $label => $value) {
            fputcsv($handle, [$label, $value]);
        }

        foreach ($document->tables as $table) {
            fputcsv($handle, []);
            fputcsv($handle, [$table->title]);
            fputcsv($handle, $table->headings);

            foreach ($table->rows as $row) {
                fputcsv($handle, $this->plainRow($row, $table));
            }

            if ($table->totals) {
                fputcsv($handle, $this->plainRow($table->totals, $table));
            }
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    private function xlsx(ReportDocument $document): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle($document->title)->setCreator($document->salonName);

        $overview = $spreadsheet->getActiveSheet();
        $overview->setTitle('Summary');
        $overview->setCellValue('A1', $document->title);
        $overview->setCellValue('A2', $document->salonName);
        $overview->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $line = 4;

        foreach ($document->meta + $document->summary as $label => $value) {
            $overview->setCellValue("A{$line}", $label);
            $overview->setCellValue("B{$line}", $value);
            $overview->getStyle("A{$line}")->getFont()->setBold(true);
            $line++;
        }

        $overview->getColumnDimension('A')->setAutoSize(true);
        $overview->getColumnDimension('B')->setAutoSize(true);

        $used = ['Summary' => true];

        foreach ($document->tables as $table) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($this->sheetTitle($table->title, $used));
            $sheet->fromArray($table->headings, null, 'A1');
            $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(1, count($table->headings)));

            $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
            $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F1F4');

            $rowNumber = 2;

            foreach ($table->rows as $row) {
                $sheet->fromArray($row, null, "A{$rowNumber}", true);
                $rowNumber++;
            }

            if ($table->totals) {
                $sheet->fromArray($table->totals, null, "A{$rowNumber}", true);
                $sheet->getStyle("A{$rowNumber}:{$lastColumn}{$rowNumber}")->getFont()->setBold(true);
                $rowNumber++;
            }

            foreach ($table->moneyColumns as $column) {
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column + 1);
                $sheet->getStyle("{$letter}2:{$letter}" . max(2, $rowNumber - 1))->getNumberFormat()->setFormatCode('#,##0.00');
            }

            foreach (range(1, max(1, count($table->headings))) as $index) {
                $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');

        return (string) ob_get_clean();
    }

    private function pdf(ReportDocument $document): string
    {
        $wide = collect($document->tables)->max(fn (ReportTable $table) => count($table->headings)) > 6;

        return Pdf::loadView('pages/apps.reports.export-pdf', ['document' => $document, 'money' => fn ($value) => $this->money($value)])
            ->setPaper('a4', $wide ? 'landscape' : 'portrait')
            ->output();
    }

    private function plainRow(array $row, ReportTable $table): array
    {
        return collect($row)->map(fn ($value, $index) => in_array($index, $table->moneyColumns, true) && is_numeric($value)
            ? number_format((float) $value, 2, '.', '')
            : $value)->all();
    }

    public function money(mixed $value): string
    {
        return is_numeric($value) ? number_format((float) $value, 2) : (string) $value;
    }

    private function sheetTitle(string $title, array &$used): string
    {
        // Excel sheet names: max 31 chars, no []:*?/\ and unique.
        $base = mb_substr(trim(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $title)) ?: 'Sheet', 0, 28);
        $name = $base;
        $suffix = 2;

        while (isset($used[mb_strtolower($name)])) {
            $name = $base . ' ' . $suffix++;
        }

        $used[mb_strtolower($name)] = true;

        return $name;
    }
}
