<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\GeneralLedgerPage;
use App\Filament\Pages\HmrcConnectionPage;
use App\Filament\Resources\AccountingPeriodResource;
use App\Filament\Resources\JournalEntryResource;
use App\Filament\Resources\LedgerAccountResource;
use App\Filament\Resources\VatAdjustmentResource;
use App\Filament\Resources\VatReturnResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingVatUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_accounting_lists_render_context_and_actionable_empty_states(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $resources = [
            LedgerAccountResource::class => ['key' => 'ledger_accounts', 'empty' => false],
            AccountingPeriodResource::class => ['key' => 'periods', 'empty' => true],
            VatAdjustmentResource::class => ['key' => 'vat_adjustments', 'empty' => true],
            JournalEntryResource::class => ['key' => 'journals', 'empty' => true],
            VatReturnResource::class => ['key' => 'vat_returns', 'empty' => true],
        ];

        foreach ($resources as $resource => $expectation) {
            $response = $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__("admin.migration.accounting.{$expectation['key']}.list_subheading"));

            if ($expectation['empty']) {
                $response->assertSeeText(__("admin.migration.accounting.{$expectation['key']}.empty_heading"));
            }
        }
    }

    public function test_general_ledger_and_hmrc_pages_render_context(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $this->get(GeneralLedgerPage::getUrl())
            ->assertOk()
            ->assertSeeText(__('admin.migration.accounting.general_ledger.subheading'));

        $this->get(HmrcConnectionPage::getUrl())
            ->assertOk()
            ->assertSeeText(__('admin.migration.accounting.hmrc.subheading'));
    }
}
