<?php

namespace App\Actions\Finance;

use App\Models\AccountingProfile;
use App\Models\Business;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Setting;
use App\Models\TaxCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\File;
use LogicException;
use RuntimeException;
use ZipArchive;

class GenerateUkAccountantDemoPackAction
{
    /**
     * @return array{directory: string, pdfs: list<string>, manifest: string, zip: string|null}
     */
    public function execute(string $directory): array
    {
        File::ensureDirectoryExists($directory);

        $fixture = require resource_path('demo/uk-accountant-pack/fixture.php');
        $this->assertControlTotals($fixture['control_totals']);
        $company = $this->company();
        $documents = $this->replaceTokens($fixture['documents'], $company);

        [$business, $client, $standardTaxCode] = $this->parties($company);
        $gbpInvoice = $this->invoice(
            $business,
            $client,
            [
                'number' => 'INV-2026-0101',
                'status' => 'paid',
                'currency' => 'GBP',
                'issue_date' => '2026-07-05',
                'tax_point' => '2026-07-04',
                'due_date' => '2026-08-04',
                'subtotal' => 6000,
                'vat_amount' => 1200,
                'total' => 7200,
                'amount_paid' => 7200,
                'amount_due' => 0,
                'subtotal_gbp' => 6000,
                'vat_amount_gbp' => 1200,
                'total_gbp' => 7200,
                'notes' => 'Thank you for your business. The company details come from the system; this demonstration uses fictional transactions.',
                'terms' => 'Payment due within 30 days by bank transfer. Quote INV-2026-0101 as the payment reference.',
            ],
            [
                $this->invoiceItem($standardTaxCode, 'Website platform discovery and architecture', 1, 4000, 4000, 800, 4800, '2026-07-04'),
                $this->invoiceItem($standardTaxCode, 'UX design and implementation sprint', 1, 2000, 2000, 400, 2400, '2026-07-04'),
            ],
        );
        $eurInvoice = $this->invoice(
            $business,
            $client,
            [
                'number' => 'INV-2026-0102',
                'status' => 'sent',
                'currency' => 'EUR',
                'issue_date' => '2026-08-10',
                'tax_point' => '2026-08-09',
                'due_date' => '2026-09-09',
                'subtotal' => 2400,
                'vat_amount' => 480,
                'total' => 2880,
                'amount_paid' => 0,
                'amount_due' => 2880,
                'gbp_exchange_rate' => 0.86,
                'exchange_rate_source' => 'HMRC monthly rate (demo snapshot)',
                'exchange_rate_date' => '2026-08-09',
                'subtotal_gbp' => 2064,
                'vat_amount_gbp' => 412.80,
                'total_gbp' => 2476.80,
                'notes' => 'Foreign-currency example. The GBP VAT value uses the immutable rate snapshot shown above.',
                'terms' => 'Payment due within 30 days by bank transfer in EUR.',
            ],
            [
                $this->invoiceItem($standardTaxCode, 'European hosting migration and setup', 1, 2400, 2400, 480, 2880, '2026-08-09', 2064, 412.80, 2476.80),
            ],
        );
        $creditNote = $this->creditNote($business, $client, $standardTaxCode, $gbpInvoice);

        $pdfs = [];
        $pdfs[] = $this->writePdf($directory, '01-uk-vat-invoice-gbp.pdf', 'pdf.invoice', [
            'invoice' => $gbpInvoice, 'demo' => true, 'demoLabel' => 'CURRENT SYSTEM OUTPUT',
        ]);
        $pdfs[] = $this->writePdf($directory, '02-uk-vat-invoice-eur.pdf', 'pdf.invoice', [
            'invoice' => $eurInvoice, 'demo' => true, 'demoLabel' => 'CURRENT SYSTEM OUTPUT · FX SNAPSHOT',
        ]);
        $pdfs[] = $this->writePdf($directory, '03-customer-credit-note.pdf', 'pdf.credit-note', [
            'creditNote' => $creditNote, 'demo' => true, 'demoLabel' => 'CURRENT SYSTEM OUTPUT',
        ]);

        foreach ($documents as $document) {
            $pdfs[] = $this->writePdf(
                $directory,
                $document['filename'],
                'pdf.demo.accountant-report',
                ['document' => $document, 'company' => $company],
                $document['orientation'] ?? 'portrait',
            );
        }

        sort($pdfs);
        $readme = $directory.DIRECTORY_SEPARATOR.'README.md';
        $reviewChecklist = $directory.DIRECTORY_SEPARATOR.'accountant-review-checklist.md';
        $dataset = $directory.DIRECTORY_SEPARATOR.'demo-dataset.json';
        $datasetPayload = $fixture['dataset'];
        $datasetPayload['company'] = [
            'legal_name' => $company['name'],
            'company_number' => $company['company_number'],
            'vat_number' => $company['vat_number'],
            'registered_address' => $company['address'],
            'email' => $company['email'],
            'phone' => $company['phone'],
            'functional_currency' => $company['functional_currency'],
            'accounting_basis' => $company['accounting_basis'],
            'source' => 'Legal Settings with AccountingProfile fallback',
            'accounting_profile_consistent' => $company['accounting_profile_consistent'],
        ];

        File::put($readme, $this->replaceTokens($fixture['readme'], $company));
        File::put($reviewChecklist, $fixture['review_checklist']);
        File::put($dataset, json_encode($datasetPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

        $manifest = $directory.DIRECTORY_SEPARATOR.'manifest.sha256.json';
        $manifestFiles = [...$pdfs, $readme, $reviewChecklist, $dataset];
        File::put($manifest, json_encode([
            'pack' => 'WebsiteExpert UK accountant demonstration pack',
            'generated_on' => '2026-11-05',
            'classification' => 'DEMO / NOT FOR SUBMISSION',
            'files' => array_map(fn (string $path): array => [
                'name' => basename($path),
                'bytes' => File::size($path),
                'sha256' => hash_file('sha256', $path),
            ], $manifestFiles),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

        $zipPath = $this->zip($directory, [...$manifestFiles, $manifest]);

        return [
            'directory' => $directory,
            'pdfs' => $pdfs,
            'manifest' => $manifest,
            'zip' => $zipPath,
        ];
    }

    /**
     * @param  array<string, mixed>  $company
     * @return array{Business, Client, TaxCode}
     */
    private function parties(array $company): array
    {
        $business = new Business(['name' => $company['name']]);
        $profile = new AccountingProfile([
            'entity_type' => 'limited_company',
            'legal_name' => $company['name'],
            'company_number' => $company['company_number'],
            'registered_address' => $company['registered_address'],
            'functional_currency' => $company['functional_currency'],
            'accounting_basis' => $company['accounting_basis'],
            'vat_registered' => filled($company['vat_number']),
            'vat_number' => $company['vat_number'],
        ]);
        $business->setRelation('accountingProfile', $profile);

        $client = new Client([
            'company_name' => 'Northstar Retail Ltd',
            'primary_contact_name' => 'Accounts Payable',
            'primary_contact_email' => 'accounts@example.test',
            'address_line1' => '42 Deansgate',
            'city' => 'Manchester',
            'postcode' => 'M3 2BW',
            'country' => 'United Kingdom',
            'vat_number' => 'GB987654321',
        ]);
        $taxCode = new TaxCode(['code' => 'S20', 'type' => 'standard', 'rate' => 20]);

        return [$business, $client, $taxCode];
    }

    /** @return array<string, mixed> */
    private function company(): array
    {
        $business = Business::query()
            ->with('accountingProfile')
            ->where('is_active', true)
            ->first();
        $profile = $business?->accountingProfile;

        $name = trim((string) Setting::get('legal.company_name', $profile?->legal_name ?: $business?->name ?: 'Company not configured'));
        $companyNumber = trim((string) Setting::get('legal.company_number', $profile?->company_number ?: ''));
        $vatNumber = trim((string) Setting::get('legal.vat_number', $profile?->vat_number ?: ''));
        $address = trim((string) Setting::get('legal.company_address', ''));
        $email = trim((string) Setting::get('legal.company_email', ''));
        $phone = trim((string) Setting::get('legal.company_phone', ''));

        if ($address === '' && is_array($profile?->registered_address)) {
            $address = implode("\n", array_filter([
                $profile->registered_address['line_1'] ?? null,
                $profile->registered_address['line_2'] ?? null,
                $profile->registered_address['city'] ?? null,
                $profile->registered_address['postcode'] ?? null,
                $profile->registered_address['country'] ?? null,
            ]));
        }

        $addressLines = array_values(array_filter(array_map(
            'trim',
            preg_split('/\R/', $address) ?: [],
        )));

        return [
            'name' => $name,
            'company_number' => $companyNumber,
            'vat_number' => $vatNumber,
            'address' => $address,
            'address_inline' => $address !== '' ? preg_replace('/\s*\R\s*/', ', ', $address) : 'Not configured',
            'email' => $email,
            'phone' => $phone,
            'functional_currency' => $profile?->functional_currency ?: 'GBP',
            'accounting_basis' => $profile?->accounting_basis ?: 'accrual',
            'registered_address' => [
                'line_1' => $addressLines[0] ?? null,
                'line_2' => count($addressLines) > 3 ? $addressLines[1] : null,
                'city' => count($addressLines) >= 2 ? $addressLines[count($addressLines) - 2] : null,
                'postcode' => $addressLines !== [] ? $addressLines[count($addressLines) - 1] : null,
                'country' => 'United Kingdom',
            ],
            'accounting_profile_consistent' => $profile !== null
                && $profile->legal_name === $name
                && (string) $profile->company_number === $companyNumber
                && (string) $profile->vat_number === $vatNumber,
        ];
    }

    private function replaceTokens(mixed $value, array $company): mixed
    {
        if (is_string($value)) {
            return strtr($value, [
                '{{company_name}}' => $company['name'],
                '{{company_number}}' => $company['company_number'] ?: 'Not configured',
                '{{vat_number}}' => $company['vat_number'] ?: 'Not configured',
                '{{company_address}}' => $company['address'] ?: 'Not configured',
                '{{company_address_inline}}' => $company['address_inline'],
                '{{company_email}}' => $company['email'] ?: 'Not configured',
                '{{company_phone}}' => $company['phone'] ?: 'Not configured',
                '{{profile_consistency}}' => $company['accounting_profile_consistent'] ? 'consistent' : 'requires synchronisation',
            ]);
        }

        if (! is_array($value)) {
            return $value;
        }

        return array_map(fn (mixed $item): mixed => $this->replaceTokens($item, $company), $value);
    }

    /** @param array<string, mixed> $attributes @param list<InvoiceItem> $items */
    private function invoice(Business $business, Client $client, array $attributes, array $items): Invoice
    {
        $invoice = new Invoice($attributes);
        $invoice->setRelation('business', $business);
        $invoice->setRelation('client', $client);
        $invoice->setRelation('project', null);
        $invoice->setRelation('items', new EloquentCollection($items));

        return $invoice;
    }

    private function invoiceItem(
        TaxCode $taxCode,
        string $description,
        float $quantity,
        float $unitPrice,
        float $net,
        float $vat,
        float $gross,
        string $supplyDate,
        ?float $netGbp = null,
        ?float $vatGbp = null,
        ?float $grossGbp = null,
    ): InvoiceItem {
        $item = new InvoiceItem([
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'net_amount' => $net,
            'vat_rate_snapshot' => 20,
            'vat_amount' => $vat,
            'gross_amount' => $gross,
            'supply_date' => $supplyDate,
            'net_amount_gbp' => $netGbp ?? $net,
            'vat_amount_gbp' => $vatGbp ?? $vat,
            'gross_amount_gbp' => $grossGbp ?? $gross,
        ]);
        $item->setRelation('taxCode', $taxCode);

        return $item;
    }

    private function creditNote(Business $business, Client $client, TaxCode $taxCode, Invoice $invoice): CreditNote
    {
        $item = new CreditNoteItem([
            'description' => 'Agreed service scope adjustment',
            'quantity' => 1,
            'unit_price' => 500,
            'supply_date' => '2026-07-04',
            'net_amount' => 500,
            'net_amount_gbp' => 500,
            'vat_rate_snapshot' => 20,
            'vat_amount' => 100,
            'vat_amount_gbp' => 100,
            'gross_amount' => 600,
            'gross_amount_gbp' => 600,
        ]);
        $item->setRelation('taxCode', $taxCode);

        $creditNote = new CreditNote([
            'number' => 'CN-2026-0003',
            'status' => CreditNote::STATUS_ALLOCATED,
            'currency' => 'GBP',
            'reason' => 'Agreed reduction in the delivered discovery scope.',
            'issue_date' => '2026-07-20',
            'tax_point' => '2026-07-04',
            'subtotal' => 500,
            'vat_amount' => 100,
            'total' => 600,
            'subtotal_gbp' => 500,
            'vat_amount_gbp' => 100,
            'total_gbp' => 600,
            'allocated_amount' => 600,
        ]);
        $creditNote->setRelation('business', $business);
        $creditNote->setRelation('client', $client);
        $creditNote->setRelation('invoice', $invoice);
        $creditNote->setRelation('items', new EloquentCollection([$item]));

        return $creditNote;
    }

    /** @param array<string, mixed> $data */
    private function writePdf(string $directory, string $filename, string $view, array $data, string $orientation = 'portrait'): string
    {
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        File::put($path, Pdf::loadView($view, $data)->setPaper('a4', $orientation)->output());

        if (File::size($path) < 1000 || file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
            throw new RuntimeException("Invalid PDF generated: {$filename}");
        }

        return $path;
    }

    /** @param array<string, float|int> $totals */
    private function assertControlTotals(array $totals): void
    {
        $checks = [
            abs(($totals['vat_box_1'] + $totals['vat_box_2'] - $totals['vat_box_4']) - $totals['vat_box_5']) < 0.001,
            abs($totals['trial_balance_debits'] - $totals['trial_balance_credits']) < 0.001,
            abs($totals['balance_sheet_assets'] - $totals['balance_sheet_equity_and_liabilities']) < 0.001,
            abs(($totals['sales'] - $totals['expenses'] + $totals['other_income']) - $totals['profit']) < 0.001,
        ];

        if (in_array(false, $checks, true)) {
            throw new LogicException('The UK accountant demo fixture does not reconcile.');
        }
    }

    /** @param list<string> $files */
    private function zip(string $directory, array $files): ?string
    {
        if (! class_exists(ZipArchive::class)) {
            return null;
        }

        $path = $directory.DIRECTORY_SEPARATOR.'uk-accountant-demo-pack.zip';
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the UK accountant demonstration ZIP.');
        }

        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        return $path;
    }
}
