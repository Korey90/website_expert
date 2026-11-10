<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\ApproveVatAdjustmentAction;
use App\Actions\Finance\CreateVatAdjustmentAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\PostVatAdjustmentAction;
use App\Actions\Finance\UpdateVatAdjustmentAction;
use App\Actions\Finance\VatReturnCalculationAction;
use App\Filament\Resources\VatAdjustmentResource;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\FinancialAttachment;
use App\Models\LedgerAccount;
use App\Models\TaxCode;
use App\Models\User;
use App\Models\VatAdjustment;
use App\Services\Finance\FinancialAttachmentService;
use App\Services\Finance\VatDetailService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VatAdjustmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('financial');
        $this->business = Business::create([
            'name' => 'VAT Adjustment Books',
            'slug' => 'vat-adjustment-books',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        foreach (['access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance', 'post_finance']);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->business->accountingProfile()->update([
            'vat_registered' => true,
            'vat_number' => 'GB123456789',
            'vat_scheme' => 'standard',
            'vat_effective_from' => '2020-01-01',
        ]);
        $this->period = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_draft_can_be_created_and_updated_with_tenant_references(): void
    {
        $adjustment = $this->createAdjustment();

        $this->assertSame('VADJ-2026-001', $adjustment->number);
        $this->assertSame(VatAdjustment::STATUS_DRAFT, $adjustment->status);
        $this->assertSame('25.00', $adjustment->amount_gbp);
        $this->assertSame($this->user->id, $adjustment->created_by);

        $updated = app(UpdateVatAdjustmentAction::class)->execute($adjustment, $this->user, $this->payload([
            'direction' => VatAdjustment::DIRECTION_DECREASE,
            'amount_gbp' => '12.34',
            'reason' => 'Updated calculation supported by the VAT control reconciliation.',
        ]));

        $this->assertSame(VatAdjustment::DIRECTION_DECREASE, $updated->direction);
        $this->assertSame('12.34', $updated->amount_gbp);

        $otherBusiness = Business::create(['name' => 'Other VAT Books', 'slug' => 'other-vat-books', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $foreignAccount = LedgerAccount::withoutGlobalScopes()->where('business_id', $otherBusiness->id)->where('code', '7900')->firstOrFail();

        $this->assertDomainFailure(
            fn () => app(CreateVatAdjustmentAction::class)->execute($this->business, $this->user, $this->payload([
                'contra_ledger_account_id' => $foreignAccount->id,
            ])),
            'same business',
        );

        $outsider = User::factory()->create();
        $outsider->givePermissionTo('manage_finance');
        $this->assertDomainFailure(
            fn () => app(CreateVatAdjustmentAction::class)->execute($this->business, $outsider, $this->payload()),
            'does not belong',
        );
    }

    public function test_approval_requires_permission_and_clean_evidence(): void
    {
        $adjustment = $this->createAdjustment();

        $this->assertDomainFailure(
            fn () => app(ApproveVatAdjustmentAction::class)->execute($adjustment, $this->user),
            'clean VAT adjustment evidence',
        );

        $attachment = app(FinancialAttachmentService::class)->store(
            $adjustment,
            UploadedFile::fake()->createWithContent('calculation.pdf', 'VAT adjustment calculation and source evidence'),
            $this->user,
            'vat_adjustment_evidence',
        );
        $this->assertSame(FinancialAttachment::SCAN_PENDING, $attachment->scan_status);
        $this->assertDomainFailure(
            fn () => app(ApproveVatAdjustmentAction::class)->execute($adjustment, $this->user),
            'clean VAT adjustment evidence',
        );

        $attachment->update(['scan_status' => FinancialAttachment::SCAN_CLEAN]);
        $this->user->revokePermissionTo('approve_finance');
        $this->assertDomainFailure(
            fn () => app(ApproveVatAdjustmentAction::class)->execute($adjustment, $this->user),
            'cannot approve',
        );

        $this->user->givePermissionTo('approve_finance');
        $approved = app(ApproveVatAdjustmentAction::class)->execute($adjustment, $this->user);

        $this->assertSame(VatAdjustment::STATUS_APPROVED, $approved->status);
        $this->assertSame($this->user->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);
        $this->assertCount(1, $approved->attachments);
    }

    public function test_posting_is_idempotent_and_updates_vat_detail_and_return_without_net_base(): void
    {
        $adjustment = $this->approvedAdjustment(['amount_gbp' => '25.00']);

        $posted = app(PostVatAdjustmentAction::class)->execute($adjustment, $this->user);
        $this->user->revokePermissionTo('post_finance');
        $this->assertDomainFailure(
            fn () => app(PostVatAdjustmentAction::class)->execute($posted, $this->user),
            'cannot post',
        );
        $this->user->givePermissionTo('post_finance');
        $second = app(PostVatAdjustmentAction::class)->execute($posted, $this->user);
        $journal = $posted->journalEntries->first();
        $control = $journal->lines->firstWhere('ledger_account_id', $posted->control_ledger_account_id);
        $contra = $journal->lines->firstWhere('ledger_account_id', $posted->contra_ledger_account_id);

        $this->assertSame(VatAdjustment::STATUS_POSTED, $posted->status);
        $this->assertSame($this->user->id, $posted->posted_by);
        $this->assertNotNull($posted->posted_at);
        $this->assertSame($posted->id, $second->id);
        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertSame('0.00', $control->debit_gbp);
        $this->assertSame('25.00', $control->credit_gbp);
        $this->assertSame($posted->tax_code_id, $control->tax_code_id);
        $this->assertSame('25.00', $contra->debit_gbp);
        $this->assertSame('0.00', $contra->credit_gbp);
        $this->assertNull($contra->tax_code_id);

        $detail = app(VatDetailService::class)->report($this->business->id, '2026-11-01', '2026-11-30');
        $row = $detail['rows']->firstWhere('reference', $posted->number);
        $this->assertNotNull($row);
        $this->assertSame('vat_adjustment', $row->kind);
        $this->assertTrue($row->is_adjustment);
        $this->assertSame(25.0, $row->output_vat_gbp);
        $this->assertSame(0.0, $row->net_sales_gbp);
        $this->assertSame(0.0, $row->net_purchases_gbp);
        $this->assertTrue($detail['totals']->is_reconciled);

        $return = app(VatReturnCalculationAction::class)->execute($this->business->id, '2026-11-01', '2026-11-30');
        $this->assertSame(25.0, $return['boxes'][1]->submission_value);
        $this->assertSame(25.0, $return['boxes'][5]->submission_value);
        $this->assertSame(0, $return['boxes'][6]->submission_value);
        $this->assertTrue($return['status']->is_ready_for_review);
        $this->assertContains('ledger_adjustments_present', $return['warnings']->pluck('code'));
    }

    public function test_all_vat_sides_and_directions_post_the_expected_control_side(): void
    {
        $cases = [
            [VatAdjustment::SIDE_OUTPUT, VatAdjustment::DIRECTION_INCREASE, 'credit_gbp'],
            [VatAdjustment::SIDE_OUTPUT, VatAdjustment::DIRECTION_DECREASE, 'debit_gbp'],
            [VatAdjustment::SIDE_INPUT, VatAdjustment::DIRECTION_INCREASE, 'debit_gbp'],
            [VatAdjustment::SIDE_INPUT, VatAdjustment::DIRECTION_DECREASE, 'credit_gbp'],
        ];

        foreach ($cases as $index => [$side, $direction, $amountColumn]) {
            $adjustment = $this->approvedAdjustment([
                'vat_side' => $side,
                'direction' => $direction,
                'amount_gbp' => (string) (10 + $index),
                'control_ledger_account_id' => $this->account($side === VatAdjustment::SIDE_OUTPUT ? '2200' : '1300')->id,
            ], 'evidence-'.$index.'.pdf');
            $posted = app(PostVatAdjustmentAction::class)->execute($adjustment, $this->user);
            $control = $posted->journalEntries->first()->lines->firstWhere('ledger_account_id', $posted->control_ledger_account_id);
            $opposite = $amountColumn === 'debit_gbp' ? 'credit_gbp' : 'debit_gbp';

            $this->assertSame(number_format(10 + $index, 2, '.', ''), $control->{$amountColumn});
            $this->assertSame('0.00', $control->{$opposite});
        }
    }

    public function test_error_correction_rules_require_safe_hmrc_method_and_dates(): void
    {
        $base = [
            'adjustment_type' => VatAdjustment::TYPE_ERROR_CORRECTION,
            'correction_method' => VatAdjustment::METHOD_CURRENT_RETURN,
            'discovered_on' => '2026-11-01',
            'original_period_from' => '2026-04-01',
            'original_period_to' => '2026-06-30',
        ];

        $this->assertDomainFailure(
            fn () => $this->createAdjustment([...$base, 'is_deliberate' => true]),
            'reported separately',
        );
        $this->assertDomainFailure(
            fn () => $this->createAdjustment([...$base, 'original_period_to' => '2026-12-31']),
            'must precede',
        );

        $valid = $this->createAdjustment([
            ...$base,
            'correction_method' => VatAdjustment::METHOD_SEPARATE_NOTIFICATION,
            'is_deliberate' => true,
            'amount_gbp' => '60000.00',
            'hmrc_reference' => 'HMRC-ERROR-2026-001',
        ]);

        $this->assertSame(VatAdjustment::METHOD_SEPARATE_NOTIFICATION, $valid->correction_method);
        $this->assertTrue($valid->is_deliberate);
        $this->assertSame('HMRC-ERROR-2026-001', $valid->hmrc_reference);

        $this->attachCleanEvidence($valid, 'separate-notification.pdf');
        app(ApproveVatAdjustmentAction::class)->execute($valid, $this->user);
        app(PostVatAdjustmentAction::class)->execute($valid, $this->user);

        $return = app(VatReturnCalculationAction::class)->execute($this->business->id, '2026-11-01', '2026-11-30');
        $this->assertSame(0.0, $return['boxes'][1]->submission_value);
        $this->assertTrue($return['status']->is_ready_for_review);
        $this->assertContains('separate_notification_excluded', $return['warnings']->pluck('code'));
        $this->assertSame(60000.0, $return['vat_detail']['totals']->output_vat_gbp);
    }

    public function test_current_return_error_limits_use_the_aggregate_net_error(): void
    {
        $errorFields = [
            'adjustment_type' => VatAdjustment::TYPE_ERROR_CORRECTION,
            'correction_method' => VatAdjustment::METHOD_CURRENT_RETURN,
            'discovered_on' => '2026-11-01',
            'original_period_from' => '2026-04-01',
            'original_period_to' => '2026-06-30',
        ];

        $output = $this->approvedAdjustment([
            ...$errorFields,
            'amount_gbp' => '12000.00',
        ], 'output-error.pdf');
        app(PostVatAdjustmentAction::class)->execute($output, $this->user);

        $input = $this->approvedAdjustment([
            ...$errorFields,
            'vat_side' => VatAdjustment::SIDE_INPUT,
            'control_ledger_account_id' => $this->account('1300')->id,
            'amount_gbp' => '3000.00',
        ], 'input-error.pdf');
        app(PostVatAdjustmentAction::class)->execute($input, $this->user);

        $withinLimit = app(VatReturnCalculationAction::class)->execute($this->business->id, '2026-11-01', '2026-11-30');
        $this->assertSame(12000.0, $withinLimit['boxes'][1]->submission_value);
        $this->assertSame(3000.0, $withinLimit['boxes'][4]->submission_value);
        $this->assertTrue($withinLimit['status']->is_ready_for_review);
        $this->assertNotContains('error_correction_separate_notification_required', $withinLimit['issues']->pluck('code'));

        $excess = $this->approvedAdjustment([
            ...$errorFields,
            'amount_gbp' => '2000.00',
        ], 'excess-error.pdf');
        app(PostVatAdjustmentAction::class)->execute($excess, $this->user);

        $overLimit = app(VatReturnCalculationAction::class)->execute($this->business->id, '2026-11-01', '2026-11-30');
        $this->assertFalse($overLimit['status']->is_ready_for_review);
        $this->assertContains('error_correction_separate_notification_required', $overLimit['issues']->pluck('code'));

        app(PostJournalEntryAction::class)->execute($this->business, $this->user, [
            'entry_date' => '2026-11-10',
            'description' => 'Taxable sales establishing the box 6 error threshold',
            'idempotency_key' => 'vat-adjustment:test:box-6-threshold',
            'source_snapshot' => ['test' => true],
            'lines' => [
                ['ledger_account_id' => $this->account('1200')->id, 'debit_gbp' => '1440000.00', 'credit_gbp' => '0.00'],
                ['ledger_account_id' => $this->account('4100')->id, 'tax_code_id' => $this->taxCode('S20')->id, 'debit_gbp' => '0.00', 'credit_gbp' => '1200000.00'],
                ['ledger_account_id' => $this->account('2200')->id, 'tax_code_id' => $this->taxCode('S20')->id, 'debit_gbp' => '0.00', 'credit_gbp' => '240000.00'],
            ],
        ]);

        $withinOnePercent = app(VatReturnCalculationAction::class)->execute($this->business->id, '2026-11-01', '2026-11-30');
        $this->assertSame(1200000, $withinOnePercent['boxes'][6]->submission_value);
        $this->assertTrue($withinOnePercent['status']->is_ready_for_review);

        $aboveOnePercent = $this->approvedAdjustment([
            ...$errorFields,
            'amount_gbp' => '2000.00',
        ], 'above-one-percent-error.pdf');
        app(PostVatAdjustmentAction::class)->execute($aboveOnePercent, $this->user);

        $outsideLimit = app(VatReturnCalculationAction::class)->execute($this->business->id, '2026-11-01', '2026-11-30');
        $this->assertFalse($outsideLimit['status']->is_ready_for_review);
        $this->assertContains('error_correction_separate_notification_required', $outsideLimit['issues']->pluck('code'));
    }

    public function test_approved_data_is_immutable_and_closed_period_rolls_back_posting(): void
    {
        $adjustment = $this->approvedAdjustment();

        $this->assertDomainFailure(fn () => $adjustment->update(['reason' => 'Changed after approval']), 'immutable');
        $this->assertDomainFailure(fn () => $adjustment->delete(), 'cannot be deleted');

        $this->period->update(['status' => AccountingPeriod::STATUS_CLOSED]);
        $this->assertDomainFailure(
            fn () => app(PostVatAdjustmentAction::class)->execute($adjustment, $this->user),
            'is closed',
        );

        $this->assertSame(VatAdjustment::STATUS_APPROVED, $adjustment->fresh()->status);
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_filament_pages_and_tenant_audit_enforce_permissions_and_ownership(): void
    {
        $adjustment = $this->createAdjustment();

        $this->actingAs($this->user)
            ->get(VatAdjustmentResource::getUrl('index'))
            ->assertOk()
            ->assertSee($adjustment->number);
        $this->get(VatAdjustmentResource::getUrl('create'))->assertOk();
        $this->get(VatAdjustmentResource::getUrl('view', ['record' => $adjustment]))->assertOk()->assertSee($adjustment->reason);
        $this->get(VatAdjustmentResource::getUrl('edit', ['record' => $adjustment]))->assertOk();

        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $member->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $member->givePermissionTo('access_admin_panel');
        $this->actingAs($member)->get(VatAdjustmentResource::getUrl('index'))->assertForbidden();

        $this->actingAs($this->user)->artisan('finance:audit-tenants')->expectsOutputToContain('Finance tenant audit passed.')->assertExitCode(0);

        $otherBusiness = Business::create(['name' => 'Audit Other', 'slug' => 'audit-other-vat', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $foreignTaxCode = TaxCode::withoutGlobalScopes()->where('business_id', $otherBusiness->id)->where('code', 'S20')->firstOrFail();
        DB::table('vat_adjustments')->where('id', $adjustment->id)->update(['tax_code_id' => $foreignTaxCode->id]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('vat_adjustments')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    /** @param array<string, mixed> $overrides */
    private function createAdjustment(array $overrides = []): VatAdjustment
    {
        return app(CreateVatAdjustmentAction::class)->execute($this->business, $this->user, $this->payload($overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function approvedAdjustment(array $overrides = [], string $filename = 'evidence.pdf'): VatAdjustment
    {
        $adjustment = $this->createAdjustment($overrides);
        $this->attachCleanEvidence($adjustment, $filename);

        return app(ApproveVatAdjustmentAction::class)->execute($adjustment, $this->user);
    }

    private function attachCleanEvidence(VatAdjustment $adjustment, string $filename): FinancialAttachment
    {
        $attachment = app(FinancialAttachmentService::class)->store(
            $adjustment,
            UploadedFile::fake()->createWithContent($filename, 'Evidence for '.$adjustment->number.' '.$filename),
            $this->user,
            'vat_adjustment_evidence',
        );
        $attachment->update(['scan_status' => FinancialAttachment::SCAN_CLEAN]);

        return $attachment->fresh();
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        $side = $overrides['vat_side'] ?? VatAdjustment::SIDE_OUTPUT;

        return [
            'adjustment_type' => VatAdjustment::TYPE_ACCOUNTING_ADJUSTMENT,
            'vat_side' => $side,
            'direction' => VatAdjustment::DIRECTION_INCREASE,
            'correction_method' => VatAdjustment::METHOD_NOT_ERROR,
            'tax_code_id' => $this->taxCode('S20')->id,
            'control_ledger_account_id' => $this->account($side === VatAdjustment::SIDE_OUTPUT ? '2200' : '1300')->id,
            'contra_ledger_account_id' => $this->account('7900')->id,
            'effective_date' => '2026-11-10',
            'amount_gbp' => '25.00',
            'is_deliberate' => false,
            'reason' => 'Documented VAT adjustment supported by source evidence.',
            ...$overrides,
        ];
    }

    private function account(string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()->where('business_id', $this->business->id)->where('code', $code)->firstOrFail();
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::withoutGlobalScopes()->where('business_id', $this->business->id)->where('code', $code)->firstOrFail();
    }

    private function assertDomainFailure(callable $callback, string $message): void
    {
        try {
            $callback();
            $this->fail('Expected a domain exception containing: '.$message);
        } catch (DomainException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }
}
