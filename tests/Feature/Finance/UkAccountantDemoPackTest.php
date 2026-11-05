<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\GenerateUkAccountantDemoPackAction;
use App\Models\AccountingProfile;
use App\Models\Business;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class UkAccountantDemoPackTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_a_reconciled_accountant_review_pack(): void
    {
        $business = Business::create([
            'name' => 'System Company Ltd',
            'slug' => 'system-company',
            'is_active' => true,
        ]);
        AccountingProfile::create([
            'business_id' => $business->id,
            'entity_type' => 'limited_company',
            'legal_name' => 'Legacy Profile Ltd',
            'functional_currency' => 'GBP',
            'accounting_basis' => 'accrual',
            'vat_registered' => false,
        ]);
        Setting::set('legal.company_name', 'Database Legal Name Ltd', 'legal');
        Setting::set('legal.company_number', '99887766', 'legal');
        Setting::set('legal.vat_number', 'GB112233445', 'legal');
        Setting::set('legal.company_address', "10 System Street\nBelfast\nBT1 1AA", 'legal');
        Setting::set('legal.company_email', 'accounts@example.test', 'legal');
        Setting::set('legal.company_phone', '+442800000000', 'legal');

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'websiteexpert-uk-demo-'.Str::uuid();

        try {
            $result = app(GenerateUkAccountantDemoPackAction::class)->execute($directory);

            $this->assertCount(18, $result['pdfs']);
            foreach ($result['pdfs'] as $pdf) {
                $this->assertFileExists($pdf);
                $this->assertGreaterThan(1000, File::size($pdf));
                $this->assertSame('%PDF-', file_get_contents($pdf, false, null, 0, 5));
            }

            $this->assertFileExists($result['manifest']);
            $manifest = json_decode(File::get($result['manifest']), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('DEMO / NOT FOR SUBMISSION', $manifest['classification']);
            $this->assertCount(21, $manifest['files']);

            foreach ($manifest['files'] as $entry) {
                $path = $directory.DIRECTORY_SEPARATOR.$entry['name'];
                $this->assertFileExists($path);
                $this->assertSame($entry['sha256'], hash_file('sha256', $path));
                $this->assertSame($entry['bytes'], File::size($path));
            }

            $dataset = json_decode(File::get($directory.DIRECTORY_SEPARATOR.'demo-dataset.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('Database Legal Name Ltd', $dataset['company']['legal_name']);
            $this->assertSame('99887766', $dataset['company']['company_number']);
            $this->assertSame('GB112233445', $dataset['company']['vat_number']);
            $this->assertFalse($dataset['company']['accounting_profile_consistent']);
            $vat = $dataset['vat_return'];
            $this->assertEqualsWithDelta($vat['box_5'], $vat['box_1'] + $vat['box_2'] - $vat['box_4'], 0.001);
            $this->assertEqualsWithDelta($dataset['trial_balance']['debits'], $dataset['trial_balance']['credits'], 0.001);
            $this->assertEqualsWithDelta($dataset['balance_sheet']['assets'], $dataset['balance_sheet']['equity_and_liabilities'], 0.001);
            $this->assertEqualsWithDelta(
                $dataset['profit_and_loss']['profit'],
                $dataset['profit_and_loss']['net_sales'] - $dataset['profit_and_loss']['operating_expenses'] + $dataset['profit_and_loss']['other_income'],
                0.001,
            );
            $bank = $dataset['bank_reconciliation'];
            $this->assertEqualsWithDelta(
                $bank['ledger_balance'],
                $bank['statement_balance'] + $bank['deposits_in_transit'] - $bank['unpresented_payments'],
                0.001,
            );

            if ($result['zip'] !== null) {
                $this->assertFileExists($result['zip']);
                $this->assertGreaterThan(1000, File::size($result['zip']));
            }
        } finally {
            if (File::isDirectory($directory)) {
                File::deleteDirectory($directory);
            }
        }
    }
}
