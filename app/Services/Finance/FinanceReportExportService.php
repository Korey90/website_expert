<?php

namespace App\Services\Finance;

use Barryvdh\DomPDF\Facade\Pdf;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class FinanceReportExportService
{
    /**
     * @param  array<string, mixed>  $document
     */
    public function download(array $document, string $format): Response
    {
        return match ($format) {
            'csv' => $this->csv($document),
            'xlsx' => $this->xlsx($document),
            'pdf' => $this->pdf($document),
            default => throw new InvalidArgumentException('Unsupported finance report export format.'),
        };
    }

    /** @param array<string, mixed> $document */
    private function csv(array $document): Response
    {
        return response()->streamDownload(function () use ($document): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                throw new InvalidArgumentException('Unable to open CSV output stream.');
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [$document['title']]);
            fputcsv($stream, [__('finance.exports.business'), $this->csvText($document['business_name'])]);
            fputcsv($stream, [__('finance.exports.generated_at'), $this->csvText($document['generated_at'])]);
            foreach ($document['metadata'] as $label => $value) {
                fputcsv($stream, [$this->csvText($label), $this->csvText($value)]);
            }

            foreach ($document['tables'] as $table) {
                fputcsv($stream, []);
                if (filled($table['title'] ?? null)) {
                    fputcsv($stream, [$this->csvText($table['title'])]);
                }
                fputcsv($stream, array_map(fn (array $column): string => $this->csvText($column['label']), $table['columns']));
                foreach ($table['rows'] as $row) {
                    fputcsv($stream, array_map(function (mixed $value, int $index) use ($table): mixed {
                        $type = (string) ($table['columns'][$index]['type'] ?? 'text');

                        return in_array($type, ['money', 'number', 'integer', 'percent'], true) && is_numeric($value)
                            ? $value
                            : $this->csvText($value);
                    }, $row['values'], array_keys($row['values'])));
                }
            }

            fclose($stream);
        }, $document['filename'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @param array<string, mixed> $document */
    private function xlsx(array $document): Response
    {
        return response()->streamDownload(function () use ($document): void {
            $spreadsheet = $this->spreadsheet($document);
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $document['filename'].'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @param array<string, mixed> $document */
    private function pdf(array $document): Response
    {
        $content = Pdf::loadView('pdf.finance-report', ['document' => $document])
            ->setPaper($document['paper'] ?? 'a4', $document['orientation'] ?? 'landscape')
            ->output();

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$document['filename'].'.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @param array<string, mixed> $document */
    private function spreadsheet(array $document): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator((string) $document['business_name'])
            ->setTitle((string) $document['title'])
            ->setSubject((string) $document['title']);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->sheetTitle((string) $document['title']));
        $columnCount = max(1, ...array_map(
            fn (array $table): int => count($table['columns']),
            $document['tables'],
        ));
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $row = 1;

        $sheet->setCellValue('A'.$row, $document['title']);
        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(26);
        $row++;

        foreach ([
            __('finance.exports.business') => $document['business_name'],
            __('finance.exports.generated_at') => $document['generated_at'],
            ...$document['metadata'],
        ] as $label => $value) {
            $sheet->setCellValueExplicit('A'.$row, (string) $label, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B'.$row, (string) $value, DataType::TYPE_STRING);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            $row++;
        }

        $row++;
        $firstHeaderRow = null;

        foreach ($document['tables'] as $table) {
            if (filled($table['title'] ?? null)) {
                $sheet->setCellValueExplicit('A'.$row, (string) $table['title'], DataType::TYPE_STRING);
                $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1F1F1F']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']],
                ]);
                $row++;
            }

            $headerRow = $row;
            $firstHeaderRow ??= $headerRow;
            foreach ($table['columns'] as $index => $column) {
                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($index + 1).$row,
                    (string) $column['label'],
                    DataType::TYPE_STRING,
                );
            }
            $tableLastColumn = Coordinate::stringFromColumnIndex(count($table['columns']));
            $sheet->getStyle("A{$row}:{$tableLastColumn}{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1F4E78']]],
            ]);
            $row++;

            foreach ($table['rows'] as $tableRow) {
                foreach ($table['columns'] as $index => $column) {
                    $cell = Coordinate::stringFromColumnIndex($index + 1).$row;
                    $value = $tableRow['values'][$index] ?? null;
                    $this->setSpreadsheetValue($sheet, $cell, $value, (string) ($column['type'] ?? 'text'));
                }

                $style = $tableRow['style'] ?? 'data';
                if (in_array($style, ['subtotal', 'total'], true)) {
                    $sheet->getStyle("A{$row}:{$tableLastColumn}{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $style === 'total' ? 'D9EAD3' : 'EDEDED'],
                        ],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '808080']]],
                    ]);
                }
                $row++;
            }

            $sheet->setAutoFilter("A{$headerRow}:{$tableLastColumn}".max($headerRow, $row - 1));
            $row += 2;
        }

        for ($index = 1; $index <= $columnCount; $index++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
        }

        if ($firstHeaderRow !== null) {
            $sheet->freezePane('A'.($firstHeaderRow + 1));
        }

        $sheet->getPageSetup()
            ->setOrientation(($document['orientation'] ?? 'landscape') === 'portrait'
                ? PageSetup::ORIENTATION_PORTRAIT
                : PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(($document['paper'] ?? 'a4') === 'a3' ? PageSetup::PAPERSIZE_A3 : PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.4)->setRight(0.3)->setBottom(0.4)->setLeft(0.3);
        $sheet->getHeaderFooter()->setOddFooter('&L'.$document['business_name'].'&RPage &P of &N');

        return $spreadsheet;
    }

    private function setSpreadsheetValue(Worksheet $sheet, string $cell, mixed $value, string $type): void
    {
        if ($value === null || $value === '') {
            $sheet->setCellValueExplicit($cell, '', DataType::TYPE_STRING);

            return;
        }

        if (in_array($type, ['money', 'number', 'integer', 'percent'], true) && is_numeric($value)) {
            $sheet->setCellValue($cell, (float) $value);
            $format = match ($type) {
                'money' => '£#,##0.00;[Red]-£#,##0.00',
                'integer' => '0',
                'percent' => '0.00"%"',
                default => '#,##0.00;[Red]-#,##0.00',
            };
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($format);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            return;
        }

        $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
    }

    private function sheetTitle(string $title): string
    {
        $title = str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $title);

        return mb_substr(trim($title), 0, 31) ?: 'Report';
    }

    private function csvText(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
