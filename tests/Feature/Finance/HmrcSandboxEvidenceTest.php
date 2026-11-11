<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\HmrcConnection;
use App\Models\HmrcVatObligation;
use App\Models\User;
use App\Services\Hmrc\Data\HmrcApiResponse;
use App\Services\Hmrc\Exceptions\HmrcFraudPreventionException;
use App\Services\Hmrc\HmrcFraudPreventionFeedbackSummary;
use App\Services\Hmrc\HmrcSandboxEvidenceExporter;
use App\Services\Hmrc\HmrcSandboxEvidenceRecorder;
use DomainException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HmrcSandboxEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private string $outputDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-11 16:30:00 UTC');
        config()->set('hmrc.environment', 'sandbox');
        config()->set('hmrc.fraud_prevention.product_id', 'website-expert-ni');
        config()->set('hmrc.fraud_prevention.product_version', '2026.11.11');
        $this->business = Business::create([
            'name' => 'Website Expert NI',
            'slug' => 'website-expert-ni-evidence',
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
        $this->outputDirectory = storage_path('framework/testing/hmrc-evidence-'.$this->business->id);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->outputDirectory);
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_records_and_verifies_a_redacted_append_only_evidence_chain(): void
    {
        $recorder = app(HmrcSandboxEvidenceRecorder::class);
        $first = $recorder->record(
            $this->business,
            $this->owner,
            HmrcSandboxEvidenceRecorder::OPERATION_FPH_VALIDATION,
            'valid',
            'corr-fph-1',
            ['code' => 'VALID_HEADERS', 'spec_version' => '3.3', 'errors' => 0, 'warnings' => 0],
        );
        $second = $recorder->record(
            $this->business,
            $this->owner,
            HmrcSandboxEvidenceRecorder::OPERATION_OBLIGATIONS,
            'succeeded',
            'corr-obligations-1',
            ['created' => 1, 'updated' => 0, 'unchanged' => 0, 'total' => 1],
        );

        $this->assertSame(1, $first->sequence);
        $this->assertSame(2, $second->sequence);
        $this->assertSame($first->event_hash, $second->previous_hash);
        $this->assertSame(['count' => 2, 'head_hash' => $second->event_hash], $recorder->verify($this->business));
        $this->assertArrayNotHasKey('summary', $second->toArray());
        $this->assertArrayNotHasKey('correlation_id', $second->toArray());

        $this->expectException(DomainException::class);
        $second->update(['outcome' => 'invalid']);
    }

    public function test_integrity_verification_detects_database_tampering(): void
    {
        $event = app(HmrcSandboxEvidenceRecorder::class)->record(
            $this->business,
            $this->owner,
            HmrcSandboxEvidenceRecorder::OPERATION_VAT_RETURN,
            'succeeded',
            'corr-return-1',
            ['box_count' => 9],
        );
        DB::table('hmrc_sandbox_evidence_events')->where('id', $event->id)->update(['outcome' => 'invalid']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('integrity');
        app(HmrcSandboxEvidenceRecorder::class)->verify($this->business);
    }

    public function test_actor_cannot_record_evidence_for_another_tenant(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Business',
            'slug' => 'other-hmrc-evidence',
            'is_active' => true,
        ]);

        try {
            app(HmrcSandboxEvidenceRecorder::class)->record(
                $otherBusiness,
                $this->owner,
                HmrcSandboxEvidenceRecorder::OPERATION_PAYMENTS,
                'succeeded',
                'corr-cross-tenant',
                ['item_count' => 1],
            );
            $this->fail('A foreign tenant must reject the evidence event.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Invalid HMRC sandbox evidence event', $exception->getMessage());
        }

        $this->assertDatabaseCount('hmrc_sandbox_evidence_events', 0);
    }

    public function test_export_excludes_tokens_vrn_period_keys_and_internal_identifiers(): void
    {
        HmrcConnection::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'environment' => 'sandbox',
            'scopes' => ['write:vat', 'read:vat'],
            'access_token' => 'secret-access-token',
            'refresh_token' => 'secret-refresh-token',
            'vrn' => '123456789',
            'expires_at' => now()->addHours(3),
            'status' => HmrcConnection::STATUS_CONNECTED,
            'connected_at' => now(),
            'connected_by' => $this->owner->id,
            'last_successful_call_at' => now(),
        ]);
        HmrcVatObligation::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'environment' => 'sandbox',
            'period_key' => 'SECRET26A3',
            'start_date' => '2026-07-01',
            'end_date' => '2026-09-30',
            'due_date' => '2026-11-07',
            'status' => HmrcVatObligation::STATUS_OPEN,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
        app(HmrcSandboxEvidenceRecorder::class)->record(
            $this->business,
            $this->owner,
            HmrcSandboxEvidenceRecorder::OPERATION_LIABILITIES,
            'succeeded',
            'corr-liabilities-1',
            ['item_count' => 2],
        );

        $document = app(HmrcSandboxEvidenceExporter::class)->build($this->business);
        $json = json_encode($document, JSON_THROW_ON_ERROR);

        $this->assertSame('website-expert-ni-hmrc-sandbox-evidence-v1', $document['schema']);
        $this->assertTrue($document['connection']['vrn_present']);
        $this->assertSame(['read:vat', 'write:vat'], $document['connection']['scopes']);
        $this->assertSame(1, $document['obligations']['open_count']);
        $this->assertSame(1, $document['integrity']['evidence_event_count']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $document['payload_sha256']);
        $this->assertStringNotContainsString('secret-access-token', $json);
        $this->assertStringNotContainsString('secret-refresh-token', $json);
        $this->assertStringNotContainsString('123456789', $json);
        $this->assertStringNotContainsString('SECRET26A3', $json);
        $this->assertStringNotContainsString((string) $this->owner->email, $json);
        $this->assertStringNotContainsString((string) $this->business->id, $json);

        $this->artisan('hmrc:export-sandbox-evidence', [
            'business' => $this->business->slug,
            '--output' => $this->outputDirectory,
        ])->assertSuccessful()->expectsOutputToContain('Evidence events: 1');

        $files = glob($this->outputDirectory.DIRECTORY_SEPARATOR.'*.json');
        $this->assertCount(1, $files);
        $this->assertFileExists($files[0].'.sha256');
        $contents = (string) file_get_contents($files[0]);
        $this->assertStringNotContainsString('secret-access-token', $contents);
        $this->assertStringStartsWith(hash('sha256', $contents), (string) file_get_contents($files[0].'.sha256'));
    }

    public function test_recording_and_export_are_blocked_outside_sandbox(): void
    {
        config()->set('hmrc.environment', 'production');

        try {
            app(HmrcSandboxEvidenceRecorder::class)->record(
                $this->business,
                $this->owner,
                HmrcSandboxEvidenceRecorder::OPERATION_PAYMENTS,
                'succeeded',
                null,
                ['item_count' => 0],
            );
            $this->fail('Production must not write sandbox evidence.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('sandbox', $exception->getMessage());
        }

        $this->artisan('hmrc:export-sandbox-evidence', ['business' => $this->business->slug])
            ->assertFailed()
            ->expectsOutputToContain('HMRC_ENVIRONMENT=sandbox');
    }

    public function test_feedback_summary_accepts_only_a_bounded_request_list(): void
    {
        $summary = app(HmrcFraudPreventionFeedbackSummary::class)->fromResponse(
            new HmrcApiResponse(200, ['requests' => [[], []]], 'corr-feedback-1'),
        );

        $this->assertSame(2, $summary['request_count']);
        $this->assertSame('corr-feedback-1', $summary['correlation_id']);
        $this->assertSame('2026-11-11T16:30:00+00:00', $summary['checked_at']);

        $this->expectException(HmrcFraudPreventionException::class);
        $this->expectExceptionMessage('invalid fraud prevention feedback response');
        app(HmrcFraudPreventionFeedbackSummary::class)->fromResponse(
            new HmrcApiResponse(200, ['requests' => 'not-a-list'], null),
        );
    }
}
