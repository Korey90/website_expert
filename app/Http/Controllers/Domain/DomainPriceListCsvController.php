<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use App\Models\DomainPriceList;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DomainPriceListCsvController extends Controller
{
    private const COLUMNS = [
        'tld', 'currency', 'is_active',
        'register_price', 'renew_price', 'transfer_price',
        'wholesale_register', 'wholesale_renew', 'wholesale_transfer',
        'margin_percent', 'notes',
    ];

    /**
     * GET /admin/domain-price-lists/export
     * Streams the full DomainPriceList as a UTF-8 BOM CSV file.
     */
    public function export(): StreamedResponse
    {
        $records = DomainPriceList::orderBy('tld')->orderBy('currency')->get(self::COLUMNS);

        return response()->streamDownload(function () use ($records): void {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM — ensures Excel opens the file with the correct encoding
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, self::COLUMNS);

            foreach ($records as $record) {
                fputcsv($out, [
                    $record->tld,
                    $record->currency,
                    $record->is_active ? '1' : '0',
                    $record->register_price,
                    $record->renew_price,
                    $record->transfer_price ?? '',
                    $record->wholesale_register ?? '',
                    $record->wholesale_renew ?? '',
                    $record->wholesale_transfer ?? '',
                    $record->margin_percent,
                    $record->notes ?? '',
                ]);
            }

            fclose($out);
        }, 'domain-price-list-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * GET /admin/domain-price-lists/export-template
     * Returns an empty CSV with just the header row as a template.
     */
    public function exportTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::COLUMNS);
            // One example row so the user knows the expected format
            fputcsv($out, ['.example', 'GBP', '1', '12.99', '15.99', '9.99', '9.38', '11.50', '8.50', '30', '']);
            fclose($out);
        }, 'domain-price-list-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
