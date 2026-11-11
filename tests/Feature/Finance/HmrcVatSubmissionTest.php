<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\GenerateHmrcVatReceiptAction;
use App\Actions\Finance\ReconcileHmrcVatSubmissionAction;
use App\Actions\Finance\SubmitVatReturnAction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\HmrcConnection;
use App\Models\HmrcVatObligation;
use App\Models\HmrcVatSubmission;
use App\Models\HmrcVatSubmissionEvent;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Finance\VatReturnSnapshotService;
use App\Services\Hmrc\Exceptions\HmrcApiException;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use App\Services\Hmrc\HmrcSandboxScenario;
use App\Services\Hmrc\HmrcVatReturnPayload;
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

        $submission = $this->submit();

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
        $events = HmrcVatSubmissionEvent::withoutGlobalScopes()->orderBy('sequence')->get();
        $this->assertSame([
            'prepared',
            'submitted',
        ], $events->pluck('event_type')->all());
        $this->assertNull($events[0]->previous_hash);
        $this->assertSame($events[0]->event_hash, $events[1]->previous_hash);
        $this->assertArrayNotHasKey('metadata', $events[1]->toArray());
        $auditMetadata = $events->pluck('metadata')->toJson();
        $this->assertStringNotContainsString('sandbox-access-token', $auditMetadata);
        $this->assertStringNotContainsString('123456789', $auditMetadata);
        $this->assertStringNotContainsString('26A3', $auditMetadata);
        $this->assertStringNotContainsString('123456789012', $auditMetadata);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://test-api.service.hmrc.gov.uk/organisations/vat/123456789/returns'
                && $request->hasHeader('Authorization', 'Bearer sandbox-access-token')
                && ! $request->hasHeader('Gov-Test-Scenario')
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

    public function test_invalid_vrn_is_rejected_and_allows_only_an_explicit_audited_retry_after_correction(): void
    {
        Http::fakeSequence()
            ->push([
                'code' => 'BUSINESS_ERROR',
                'message' => 'Business validation failed for sensitive VRN 123456789.',
                'errors' => [[
                    'code' => 'VRN_INVALID',
                    'message' => 'Sensitive nested response.',
                ]],
            ], 400, ['X-CorrelationId' => 'corr-invalid-vrn'])
            ->push([
                'processingDate' => '2026-11-11T13:15:00Z',
                'formBundleNumber' => '456789012345',
            ], 201, ['X-CorrelationId' => 'corr-corrected-submit']);

        try {
            $this->submit(HmrcSandboxScenario::SUBMISSION_INVALID_VRN);
            $this->fail('The invalid VRN scenario should be rejected.');
        } catch (HmrcApiException $exception) {
            $this->assertSame('VRN_INVALID', $exception->errorCode);
            $this->assertSame(400, $exception->status);
            $this->assertStringNotContainsString('123456789', $exception->getMessage());
        }

        $attempt = HmrcVatSubmission::withoutGlobalScopes()->sole();
        $this->assertSame(HmrcVatSubmission::STATUS_REJECTED, $attempt->status);
        $this->assertSame('VRN_INVALID', $attempt->failure_code);
        $this->assertSame(400, $attempt->failure_status);
        $this->assertFalse($attempt->canReconcile());
        $this->assertSame(
            ['prepared', 'rejected'],
            HmrcVatSubmissionEvent::withoutGlobalScopes()->orderBy('sequence')->pluck('event_type')->all(),
        );
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader(
            'Gov-Test-Scenario',
            HmrcSandboxScenario::SUBMISSION_INVALID_VRN,
        ));

        $this->actingAs($this->owner);
        $this->assertTrue($this->owner->can('submit', $this->return));
        $corrected = $this->submit();

        $this->assertSame($attempt->id, $corrected->id);
        $this->assertSame(HmrcVatSubmission::STATUS_SUBMITTED, $corrected->status);
        $this->assertNull($corrected->failure_code);
        $this->assertSame(
            ['prepared', 'rejected', 'prepared', 'submitted'],
            HmrcVatSubmissionEvent::withoutGlobalScopes()->orderBy('sequence')->pluck('event_type')->all(),
        );
        $submissionPosts = Http::recorded()->filter(fn (array $pair): bool => $pair[0]->method() === 'POST'
            && str_contains($pair[0]->url(), '/organisations/vat/'));
        $this->assertCount(2, $submissionPosts);
        $this->assertFalse($submissionPosts->last()[0]->hasHeader('Gov-Test-Scenario'));
    }

    public function test_expired_access_token_is_refreshed_before_exactly_one_submission_post(): void
    {
        HmrcConnection::withoutGlobalScopes()->sole()->forceFill([
            'expires_at' => now()->subMinute(),
        ])->save();
        Http::fake(function (Request $request) {
            if ($request->url() === 'https://test-api.service.hmrc.gov.uk/oauth/token') {
                return Http::response([
                    'access_token' => 'refreshed-access-token',
                    'refresh_token' => 'rotated-refresh-token',
                    'token_type' => 'bearer',
                    'expires_in' => 14400,
                    'scope' => 'read:vat write:vat',
                ]);
            }

            return Http::response([
                'processingDate' => '2026-11-11T13:00:00Z',
                'formBundleNumber' => '987654321098',
            ], 201, ['X-CorrelationId' => 'corr-refreshed-submit']);
        });

        $submission = $this->submit();

        $this->assertSame(HmrcVatSubmission::STATUS_SUBMITTED, $submission->status);
        $connection = HmrcConnection::withoutGlobalScopes()->sole();
        $this->assertSame('refreshed-access-token', $connection->access_token);
        $this->assertSame('rotated-refresh-token', $connection->refresh_token);
        $this->assertSame($this->owner->id, $connection->refreshed_by);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://test-api.service.hmrc.gov.uk/oauth/token'
            && $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'sandbox-refresh-token');
        $posts = Http::recorded()->filter(fn (array $pair): bool => $pair[0]->method() === 'POST'
            && str_contains($pair[0]->url(), '/organisations/vat/'));
        $this->assertCount(1, $posts);
        $this->assertTrue($posts->first()[0]->hasHeader('Authorization', 'Bearer refreshed-access-token'));
    }

    public function test_duplicate_sandbox_scenario_is_rejected_then_available_for_get_only_reconciliation(): void
    {
        Http::fakeSequence()
            ->push([
                'code' => 'DUPLICATE_SUBMISSION',
                'message' => 'A return already exists.',
            ], 403, ['X-CorrelationId' => 'corr-duplicate'])
            ->push(['obligations' => [[
                'start' => '2026-07-01',
                'end' => '2026-09-30',
                'due' => '2026-11-07',
                'status' => 'O',
                'periodKey' => '26A3',
            ]]], 200, ['X-CorrelationId' => 'corr-duplicate-check']);

        try {
            $this->submit(HmrcSandboxScenario::SUBMISSION_DUPLICATE);
            $this->fail('The duplicate scenario should be rejected.');
        } catch (HmrcApiException $exception) {
            $this->assertSame('DUPLICATE_SUBMISSION', $exception->errorCode);
        }

        $attempt = HmrcVatSubmission::withoutGlobalScopes()->sole();
        $this->assertSame(HmrcVatSubmission::STATUS_REJECTED, $attempt->status);
        $this->assertTrue($attempt->canReconcile());
        $this->actingAs($this->owner);
        $this->assertTrue($this->owner->can('reconcile', $this->return));

        $result = $this->reconcile();

        $this->assertSame(HmrcVatSubmission::STATUS_OUTCOME_UNKNOWN, $result->status);
        $this->assertSame(HmrcVatSubmission::RECONCILIATION_PENDING_OPEN, $result->reconciliation_status);
        $posts = Http::recorded()->filter(fn (array $pair): bool => $pair[0]->method() === 'POST'
            && str_contains($pair[0]->url(), '/organisations/vat/'));
        $this->assertCount(1, $posts);
    }

    public function test_timeout_records_an_unknown_outcome_without_retry_or_sensitive_transport_details(): void
    {
        Http::fake(Http::failedConnection('sandbox-access-token 123456789 transport detail'));

        try {
            $this->submit();
            $this->fail('The timeout scenario should fail.');
        } catch (HmrcApiException $exception) {
            $this->assertSame('HMRC_UNREACHABLE', $exception->errorCode);
            $this->assertSame(503, $exception->status);
            $this->assertStringNotContainsString('sandbox-access-token', $exception->getMessage());
            $this->assertStringNotContainsString('123456789', $exception->getMessage());
        }

        $attempt = HmrcVatSubmission::withoutGlobalScopes()->sole();
        $this->assertSame(HmrcVatSubmission::STATUS_OUTCOME_UNKNOWN, $attempt->status);
        $this->assertSame('HMRC_UNREACHABLE', $attempt->failure_code);
        $this->assertTrue($attempt->canReconcile());
        Http::assertSentCount(1);
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

    public function test_reconciliation_keeps_an_open_obligation_pending_without_resubmitting(): void
    {
        $attempt = $this->unknownSubmission();
        Http::fake(['*' => Http::response(['obligations' => [[
            'start' => '2026-07-01',
            'end' => '2026-09-30',
            'due' => '2026-11-07',
            'status' => 'O',
            'periodKey' => '26A3',
        ]]], 200, ['X-CorrelationId' => 'corr-obligation-open'])]);

        $result = $this->reconcile();

        $this->assertSame($attempt->id, $result->id);
        $this->assertSame(HmrcVatSubmission::STATUS_OUTCOME_UNKNOWN, $result->status);
        $this->assertSame(HmrcVatSubmission::RECONCILIATION_PENDING_OPEN, $result->reconciliation_status);
        $this->assertSame(1, $result->reconciliation_count);
        $this->assertSame($this->owner->id, $result->reconciled_by);
        $this->assertNull($result->hmrc_received_date);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_contains($request->url(), '/obligations?')
            && ! str_contains($request->url(), '/returns'));
    }

    public function test_reconciliation_marks_a_matching_fulfilled_return_as_submitted_using_get_only(): void
    {
        $this->unknownSubmission();
        Http::fakeSequence()
            ->push(['obligations' => [[
                'start' => '2026-07-01',
                'end' => '2026-09-30',
                'due' => '2026-11-07',
                'status' => 'F',
                'received' => '2026-11-11',
                'periodKey' => '26A3',
            ]]], 200, ['X-CorrelationId' => 'corr-obligation-fulfilled'])
            ->push($this->remoteReturn(), 200, ['X-CorrelationId' => 'corr-return-match']);

        $result = $this->reconcile();

        $this->assertSame(HmrcVatSubmission::STATUS_SUBMITTED, $result->status);
        $this->assertSame(HmrcVatSubmission::RECONCILIATION_MATCHED, $result->reconciliation_status);
        $this->assertSame('2026-11-11', $result->hmrc_received_date->format('Y-m-d'));
        $this->assertNull($result->form_bundle_number);
        $this->assertNull($result->submitted_at);
        $this->assertSame(HmrcVatObligation::STATUS_FULFILLED, HmrcVatObligation::withoutGlobalScopes()->sole()->status);
        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_contains($request->url(), '/returns/26A3'));
    }

    public function test_reconciliation_records_a_conflict_when_hmrc_has_different_box_values(): void
    {
        $this->unknownSubmission();
        $remoteReturn = $this->remoteReturn();
        $remoteReturn['vatDueSales'] = 999.99;
        Http::fakeSequence()
            ->push(['obligations' => [[
                'start' => '2026-07-01',
                'end' => '2026-09-30',
                'due' => '2026-11-07',
                'status' => 'F',
                'received' => '2026-11-10',
                'periodKey' => '26A3',
            ]]])
            ->push($remoteReturn);

        $result = $this->reconcile();

        $this->assertSame(HmrcVatSubmission::STATUS_OUTCOME_CONFLICT, $result->status);
        $this->assertSame(HmrcVatSubmission::RECONCILIATION_CONFLICT, $result->reconciliation_status);
        $this->assertNull($result->submitted_at);

        Http::fake();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('unknown outcome');
        $this->reconcile();
    }

    public function test_failed_reconciliation_is_audited_and_remains_safe_to_check_again(): void
    {
        $this->unknownSubmission();
        Http::fake(['*' => Http::response([
            'code' => 'SERVER_ERROR',
            'message' => 'sensitive upstream text',
        ], 503, ['X-CorrelationId' => 'corr-reconcile-failure'])]);

        try {
            $this->reconcile();
            $this->fail('The reconciliation API failure should have been raised.');
        } catch (HmrcApiException $exception) {
            $this->assertSame('SERVER_ERROR', $exception->errorCode);
        }

        $result = HmrcVatSubmission::withoutGlobalScopes()->sole();
        $this->assertSame(HmrcVatSubmission::STATUS_OUTCOME_UNKNOWN, $result->status);
        $this->assertSame(HmrcVatSubmission::RECONCILIATION_CHECK_FAILED, $result->reconciliation_status);
        $this->assertSame(1, $result->reconciliation_count);
        $this->assertSame('SERVER_ERROR', $result->reconciliation_error_code);
        $this->assertSame(503, $result->reconciliation_error_status);
        $this->assertArrayNotHasKey('reconciliation_error_code', $result->toArray());
        $this->assertDatabaseMissing('hmrc_vat_submissions', ['reconciliation_error_code' => 'sensitive upstream text']);
        Http::assertSentCount(1);
        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST');
    }

    public function test_reconciliation_rejects_a_changed_payload_hash_before_any_hmrc_call(): void
    {
        $attempt = $this->unknownSubmission();
        $attempt->forceFill(['payload_hash' => str_repeat('0', 64)])->save();
        Http::fake();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('does not match the payload');

        try {
            $this->reconcile();
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_authorized_user_can_download_a_private_integrity_checked_pdf_receipt(): void
    {
        Http::fake(['*' => Http::response([
            'processingDate' => '2026-11-11T12:34:56Z',
            'formBundleNumber' => '123456789012',
            'paymentIndicator' => 'BANK',
            'chargeRefNumber' => 'aCxFaNx0FZsCvyWF',
        ], 201, ['X-CorrelationId' => 'corr-receipt'])]);
        $this->submit();

        $response = $this->actingAs($this->owner)
            ->get(route('admin.finance.vat-returns.hmrc-receipt', $this->return));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('X-VAT-Return-Snapshot-SHA256', $this->return->snapshot_hash);
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            (string) $response->headers->get('X-HMRC-Receipt-SHA256'),
        );
        $this->assertStringContainsString('hmrc-vat-receipt-VAT-2026-Q3-', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame(3, HmrcVatSubmissionEvent::withoutGlobalScopes()->count());
        $this->assertSame(
            'receipt_generated',
            HmrcVatSubmissionEvent::withoutGlobalScopes()->latest('sequence')->value('event_type'),
        );
    }

    public function test_receipt_generation_rejects_tampered_audit_and_events_are_immutable(): void
    {
        Http::fake(['*' => Http::response([
            'processingDate' => '2026-11-11T12:34:56Z',
            'formBundleNumber' => '123456789012',
        ], 201)]);
        $this->submit();
        $event = HmrcVatSubmissionEvent::withoutGlobalScopes()->orderBy('sequence')->firstOrFail();

        try {
            $event->update(['status' => 'changed']);
            $this->fail('Audit events must be immutable.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        DB::table('hmrc_vat_submission_events')->where('id', $event->id)->update([
            'event_hash' => str_repeat('0', 64),
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('audit chain failed');
        app(GenerateHmrcVatReceiptAction::class)->execute($this->return, $this->owner);
    }

    public function test_receipt_requires_submission_permission_and_a_complete_direct_receipt(): void
    {
        $this->unknownSubmission()->forceFill([
            'status' => HmrcVatSubmission::STATUS_SUBMITTED,
            'reconciliation_status' => HmrcVatSubmission::RECONCILIATION_MATCHED,
        ])->save();

        try {
            app(GenerateHmrcVatReceiptAction::class)->execute($this->return, $this->owner);
            $this->fail('A reconciled result without a direct HMRC receipt must not produce a receipt PDF.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('direct successful submission', $exception->getMessage());
        }

        $this->owner->revokePermissionTo('submit_tax_returns');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('cannot download');
        app(GenerateHmrcVatReceiptAction::class)->execute($this->return, $this->owner);
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

    private function reconcile(): HmrcVatSubmission
    {
        return app(ReconcileHmrcVatSubmissionAction::class)->execute(
            $this->return,
            $this->owner,
            $this->multiFactor(),
            $this->fraudHeaders(),
        );
    }

    private function unknownSubmission(): HmrcVatSubmission
    {
        $obligation = HmrcVatObligation::withoutGlobalScopes()->sole();
        $payloadService = app(HmrcVatReturnPayload::class);
        $payload = $payloadService->build($this->return, (string) $obligation->period_key);

        return HmrcVatSubmission::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'vat_return_id' => $this->return->id,
            'hmrc_vat_obligation_id' => $obligation->id,
            'environment' => 'sandbox',
            'status' => HmrcVatSubmission::STATUS_OUTCOME_UNKNOWN,
            'snapshot_hash' => $this->return->snapshot_hash,
            'payload_hash' => $payloadService->hash($payload),
            'declaration_version' => HmrcVatSubmission::DECLARATION_VERSION,
            'declared_at' => now(),
            'mfa_type' => 'TOTP',
            'mfa_verified_at' => now(),
            'mfa_reference_hash' => hash('sha256', str_repeat('a', 64)),
            'submitted_by' => $this->owner->id,
            'failure_code' => 'HMRC_UNREACHABLE',
            'failure_status' => 503,
        ]);
    }

    /** @return array<string, int|float|string> */
    private function remoteReturn(): array
    {
        return [
            'periodKey' => '26A3',
            'vatDueSales' => 100.25,
            'vatDueAcquisitions' => 0,
            'totalVatDue' => 100.25,
            'vatReclaimedCurrPeriod' => 25,
            'netVatDue' => 75.25,
            'totalValueSalesExVAT' => 500,
            'totalValuePurchasesExVAT' => 100,
            'totalValueGoodsSuppliedExVAT' => 0,
            'totalAcquisitionsExVAT' => 0,
        ];
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
