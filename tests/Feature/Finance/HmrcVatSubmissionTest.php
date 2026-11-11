<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\SubmitVatReturnAction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\HmrcConnection;
use App\Models\HmrcVatObligation;
use App\Models\HmrcVatSubmission;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Finance\VatReturnSnapshotService;
use App\Services\Hmrc\Exceptions\HmrcApiException;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HmrcVatSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private VatReturn $return;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config()->set([
            'hmrc.environment' => 'sandbox',
            'hmrc.sandbox.base_url' => 'https://test-api.service.hmrc.gov.uk',
            'hmrc.sandbox.token_url' => 'https://test-api.service.hmrc.gov.uk/oauth/token',
            'hmrc.sandbox.client_id' => 'sandbox-client-id',
            'hmrc.sandbox.client_secret' => 'sandbox-client-secret',
            'hmrc.api_version' => '1.0',
        ]);

        $this->business = Business::create([
            'name' => 'Website Expert NI',
            'slug' => 'website-expert-ni-vat-submit',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        Permission::findOrCreate('submit_tax_returns', 'web');
        $this->owner->givePermissionTo('submit_tax_returns');

        HmrcConnection::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'environment' => 'sandbox',
            'scopes' => ['read:vat', 'write:vat'],
            'access_token' => 'sandbox-access-token',
            'refresh_token' => 'sandbox-refresh-token',
            'vrn' => '123456789',
            'expires_at' => now()->addHours(3),
            'status' => HmrcConnection::STATUS_CONNECTED,
            'connected_at' => now(),
            'connected_by' => $this->owner->id,
        ]);
        HmrcVatObligation::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'environment' => 'sandbox',
            'period_key' => '26A3',
            'start_date' => '2026-07-01',
            'end_date' => '2026-09-30',
            'due_date' => '2026-11-07',
            'status' => HmrcVatObligation::STATUS_OPEN,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
        $this->return = $this->lockedReturn();
    }

    public function test_it_submits_the_immutable_locked_payload_and_stores_a_protected_receipt(): void
    {
        Http::fake(['*' => Http::response([
            'processingDate' => '2026-11-11T12:34:56Z',
            'formBundleNumber' => '123456789012',
            'paymentIndicator' => 'DD',
            'chargeRefNumber' => 'A1B2C3D4E5F6',
        ], 201, ['X-CorrelationId' => 'corr-submit-1'])]);

        $submission = $this->submit('QUARTERLY_NONE_MET');

        $this->assertSame(HmrcVatSubmission::STATUS_SUBMITTED, $submission->status);
        $this->assertSame($this->return->snapshot_hash, $submission->snapshot_hash);
        $this->assertSame($this->owner->id, $submission->submitted_by);
        $this->assertSame('DD', $submission->payment_indicator);
        $this->assertNotNull($submission->submitted_at);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $submission->payload_hash);
        $this->assertSame('123456789012', $submission->form_bundle_number);
        $this->assertSame('A1B2C3D4E5F6', $submission->charge_reference);
        $this->assertArrayNotHasKey('form_bundle_number', $submission->toArray());
        $this->assertArrayNotHasKey('charge_reference', $submission->toArray());
        $this->assertNotSame(
            '123456789012',
            DB::table('hmrc_vat_submissions')->where('id', $submission->id)->value('form_bundle_number'),
        );
        $this->assertNotSame(
            'A1B2C3D4E5F6',
            DB::table('hmrc_vat_submissions')->where('id', $submission->id)->value('charge_reference'),
        );
        $this->assertNotNull(HmrcConnection::withoutGlobalScopes()->sole()->last_successful_call_at);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://test-api.service.hmrc.gov.uk/organisations/vat/123456789/returns'
                && $request->hasHeader('Authorization', 'Bearer sandbox-access-token')
                && $request->hasHeader('Gov-Test-Scenario', 'QUARTERLY_NONE_MET')
                && $request->data() === [
                    'periodKey' => '26A3',
                    'vatDueSales' => 100.25,
                    'vatDueAcquisitions' => 0.0,
                    'totalVatDue' => 100.25,
                    'vatReclaimedCurrPeriod' => 25.0,
                    'netVatDue' => 75.25,
                    'totalValueSalesExVAT' => 500,
                    'totalValuePurchasesExVAT' => 100,
                    'totalValueGoodsSuppliedExVAT' => 0,
                    'totalAcquisitionsExVAT' => 0,
                    'finalised' => true,
                ];
        });
    }

    public function test_an_invalid_receipt_records_an_unknown_outcome_and_blocks_a_blind_retry(): void
    {
        Http::fake(['*' => Http::response(['processingDate' => 'not-a-date'], 201)]);

        try {
            $this->submit();
            $this->fail('The invalid HMRC receipt should have been rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('invalid submission receipt', $exception->getMessage());
        }

        $attempt = HmrcVatSubmission::withoutGlobalScopes()->sole();
        $this->assertSame(HmrcVatSubmission::STATUS_OUTCOME_UNKNOWN, $attempt->status);
        $this->assertSame('INVALID_RESPONSE', $attempt->failure_code);
        $this->assertNull($attempt->submitted_at);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('already exists');

        $this->submit();
    }

    public function test_an_hmrc_failure_records_only_sanitized_failure_metadata_and_blocks_retry(): void
    {
        Http::fake(['*' => Http::response([
            'code' => 'SERVER_ERROR',
            'message' => 'sensitive upstream detail',
        ], 503, ['X-CorrelationId' => 'corr-submit-failure'])]);

        try {
            $this->submit();
            $this->fail('The HMRC failure should have been raised.');
        } catch (HmrcApiException $exception) {
            $this->assertSame('SERVER_ERROR', $exception->errorCode);
        }

        $attempt = HmrcVatSubmission::withoutGlobalScopes()->sole();
        $this->assertSame(HmrcVatSubmission::STATUS_OUTCOME_UNKNOWN, $attempt->status);
        $this->assertSame('SERVER_ERROR', $attempt->failure_code);
        $this->assertSame(503, $attempt->failure_status);
        $this->assertArrayNotHasKey('failure_code', $attempt->toArray());
        $this->assertDatabaseMissing('hmrc_vat_submissions', ['failure_code' => 'sensitive upstream detail']);
    }

    public function test_it_requires_the_legal_declaration_permission_and_a_matching_open_obligation_before_posting(): void
    {
        Http::fake();

        try {
            app(SubmitVatReturnAction::class)->execute(
                $this->return,
                $this->owner,
                false,
                $this->multiFactor(),
                $this->fraudHeaders(),
            );
            $this->fail('The legal declaration should be mandatory.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('explicitly confirmed', $exception->getMessage());
        }

        $this->owner->revokePermissionTo('submit_tax_returns');

        try {
            $this->submit();
            $this->fail('The submission permission should be mandatory.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot submit', $exception->getMessage());
        }

        $this->owner->givePermissionTo('submit_tax_returns');
        HmrcVatObligation::withoutGlobalScopes()->update(['status' => HmrcVatObligation::STATUS_FULFILLED]);

        try {
            $this->submit();
            $this->fail('A matching open HMRC obligation should be mandatory.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('matching open', $exception->getMessage());
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('hmrc_vat_submissions', 0);
    }

    public function test_it_rejects_stale_mfa_and_a_tampered_locked_snapshot_before_posting(): void
    {
        Http::fake();
        $staleMfa = $this->multiFactor();
        $staleMfa['timestamp'] = now()->subMinutes(3)->utc()->format('Y-m-d\TH:i:s\Z');

        try {
            app(SubmitVatReturnAction::class)->execute(
                $this->return,
                $this->owner,
                true,
                $staleMfa,
                $this->fraudHeaders(),
            );
            $this->fail('A stale MFA factor should be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('fresh verified', $exception->getMessage());
        }

        DB::table('vat_returns')->where('id', $this->return->id)->update([
            'snapshot' => json_encode(['calculation' => ['hmrc_values' => []]], JSON_THROW_ON_ERROR),
        ]);

        try {
            $this->submit();
            $this->fail('A tampered snapshot should be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('integrity check', $exception->getMessage());
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('hmrc_vat_submissions', 0);
    }

    private function submit(?string $testScenario = null): HmrcVatSubmission
    {
        return app(SubmitVatReturnAction::class)->execute(
            $this->return,
            $this->owner,
            true,
            $this->multiFactor(),
            $this->fraudHeaders(),
            $testScenario,
        );
    }

    private function lockedReturn(): VatReturn
    {
        $snapshot = [
            'calculation' => [
                'hmrc_values' => [
                    'vatDueSales' => 100.25,
                    'vatDueAcquisitions' => 0.0,
                    'totalVatDue' => 100.25,
                    'vatReclaimedCurrPeriod' => 25.0,
                    'netVatDue' => 75.25,
                    'totalValueSalesExVAT' => 500,
                    'totalValuePurchasesExVAT' => 100,
                    'totalValueGoodsSuppliedExVAT' => 0,
                    'totalAcquisitionsExVAT' => 0,
                ],
            ],
        ];
        $hash = app(VatReturnSnapshotService::class)->hash($snapshot);
        $id = DB::table('vat_returns')->insertGetId([
            'business_id' => $this->business->id,
            'number' => 'VAT-2026-Q3',
            'status' => VatReturn::STATUS_LOCKED,
            'period_from' => '2026-07-01',
            'period_to' => '2026-09-30',
            'snapshot_version' => 1,
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION),
            'snapshot_hash' => $hash,
            'source_journal_count' => 2,
            'source_line_count' => 8,
            'warning_count' => 0,
            'created_by' => $this->owner->id,
            'reviewed_by' => $this->owner->id,
            'reviewed_at' => now(),
            'review_confirmation_version' => VatReturn::REVIEW_CONFIRMATION_VERSION,
            'locked_by' => $this->owner->id,
            'locked_at' => now(),
            'lock_reason' => 'Approved and locked for HMRC sandbox submission.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return VatReturn::withoutGlobalScopes()->findOrFail($id);
    }

    /** @return array{type: string, timestamp: string, unique_reference: string} */
    private function multiFactor(): array
    {
        return [
            'type' => 'TOTP',
            'timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'unique_reference' => str_repeat('a', 64),
        ];
    }

    /** @return array<string, string> */
    private function fraudHeaders(): array
    {
        return array_combine(
            HmrcFraudPreventionHeaders::REQUIRED_HEADERS,
            array_map(
                fn (string $header): string => $header === 'Gov-Client-Connection-Method'
                    ? 'WEB_APP_VIA_SERVER'
                    : 'test-value',
                HmrcFraudPreventionHeaders::REQUIRED_HEADERS,
            ),
        );
    }
}
