<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\RetryFinancialAttachmentScanAction;
use App\Actions\Finance\ScanFinancialAttachmentAction;
use App\Contracts\Finance\FinancialAttachmentScanner;
use App\Data\Finance\FinancialAttachmentScanResult;
use App\Jobs\ScanFinancialAttachmentJob;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Expense;
use App\Models\FinancialAttachment;
use App\Models\User;
use App\Services\Finance\ClamAvFinancialAttachmentScanner;
use App\Services\Finance\Exceptions\FinancialAttachmentScanException;
use App\Services\Finance\FinancialAttachmentService;
use DomainException;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinancialAttachmentSecurityScanTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Expense $expense;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('financial');
        Queue::fake();
        Permission::findOrCreate('manage_finance', 'web');

        $this->business = Business::create([
            'name' => 'Attachment Scanner Ltd',
            'slug' => 'attachment-scanner',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->givePermissionTo('manage_finance');
        $this->addMember($this->business, $this->user);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);

        $this->expense = app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_invoice_number' => 'SCAN-001',
            'issue_date' => '2026-11-20',
            'currency' => 'GBP',
            'items' => [[
                'description' => 'Security scan fixture',
                'quantity' => 1,
                'unit_price' => 10,
                'tax_code_id' => $this->business->taxCodes()->where('code', 'S20')->value('id'),
            ]],
        ]);
    }

    public function test_a_clean_scan_records_auditable_metadata(): void
    {
        $attachment = $this->storeAttachment('clean.pdf', 'legitimate supplier invoice');
        $this->bindScanner(FinancialAttachmentScanResult::clean('test-clamav'));

        $scanned = app(ScanFinancialAttachmentAction::class)->execute($attachment->id);

        $this->assertSame(FinancialAttachment::SCAN_CLEAN, $scanned->scan_status);
        $this->assertSame(1, $scanned->scan_attempts);
        $this->assertSame('test-clamav', $scanned->scan_engine);
        $this->assertNull($scanned->scan_error_code);
        $this->assertNotNull($scanned->scanned_at);
    }

    public function test_a_malware_result_is_rejected_and_never_marked_clean(): void
    {
        $attachment = $this->storeAttachment('eicar.pdf', 'simulated malicious fixture');
        $this->bindScanner(FinancialAttachmentScanResult::rejected('test-clamav'));

        $scanned = app(ScanFinancialAttachmentAction::class)->execute($attachment->id);

        $this->assertSame(FinancialAttachment::SCAN_REJECTED, $scanned->scan_status);
        $this->assertSame('test-clamav', $scanned->scan_engine);
        $this->assertNotNull($scanned->scanned_at);
    }

    public function test_a_tampered_file_fails_before_the_scanner_is_called(): void
    {
        $attachment = $this->storeAttachment('tampered.pdf', 'original accounting evidence');
        Storage::disk('financial')->put($attachment->path, 'changed after upload');
        $this->app->instance(FinancialAttachmentScanner::class, new class implements FinancialAttachmentScanner
        {
            public function engine(): string
            {
                return 'must-not-run';
            }

            public function scan(string $absolutePath): FinancialAttachmentScanResult
            {
                throw new \LogicException('The scanner must not receive a file with a mismatched hash.');
            }
        });

        $scanned = app(ScanFinancialAttachmentAction::class)->execute($attachment->id);

        $this->assertSame(FinancialAttachment::SCAN_FAILED, $scanned->scan_status);
        $this->assertSame('integrity_mismatch', $scanned->scan_error_code);
        $this->assertNotNull($scanned->scanned_at);
    }

    public function test_a_retryable_scanner_failure_remains_pending_until_the_job_exhausts_retries(): void
    {
        $attachment = $this->storeAttachment('scanner-error.pdf', 'legitimate evidence');
        $this->app->instance(FinancialAttachmentScanner::class, new class implements FinancialAttachmentScanner
        {
            public function engine(): string
            {
                return 'test-clamav';
            }

            public function scan(string $absolutePath): FinancialAttachmentScanResult
            {
                throw new FinancialAttachmentScanException('scanner_unavailable');
            }
        });

        try {
            app(ScanFinancialAttachmentAction::class)->execute($attachment->id);
            $this->fail('A retryable scanner failure should be rethrown to the queue.');
        } catch (FinancialAttachmentScanException $exception) {
            $this->assertSame('scanner_unavailable', $exception->errorCode);
        }

        $attachment->refresh();
        $this->assertSame(FinancialAttachment::SCAN_PENDING, $attachment->scan_status);
        $this->assertSame('scanner_unavailable', $attachment->scan_error_code);
        $this->assertNull($attachment->scanned_at);

        (new ScanFinancialAttachmentJob($attachment->id))->failed(null);

        $attachment->refresh();
        $this->assertSame(FinancialAttachment::SCAN_FAILED, $attachment->scan_status);
        $this->assertSame('scanner_unavailable', $attachment->scan_error_code);
        $this->assertNotNull($attachment->scanned_at);
    }

    public function test_manual_retry_is_tenant_scoped_and_dispatches_only_retryable_states(): void
    {
        $attachment = $this->storeAttachment('retry.pdf', 'retryable evidence');
        $attachment->update([
            'scan_status' => FinancialAttachment::SCAN_FAILED,
            'scan_error_code' => 'scanner_unavailable',
            'scanned_at' => now(),
        ]);

        $outsider = User::factory()->create();
        $outsider->givePermissionTo('manage_finance');

        try {
            app(RetryFinancialAttachmentScanAction::class)->execute($attachment, $outsider);
            $this->fail('A user outside the tenant must not retry a scan.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot retry', $exception->getMessage());
        }

        Queue::fake();
        $retried = app(RetryFinancialAttachmentScanAction::class)->execute($attachment, $this->user);

        $this->assertSame(FinancialAttachment::SCAN_PENDING, $retried->scan_status);
        $this->assertNull($retried->scan_error_code);
        $this->assertNotNull($retried->scan_requested_at);
        Queue::assertPushed(
            ScanFinancialAttachmentJob::class,
            fn (ScanFinancialAttachmentJob $job): bool => $job->attachmentId === $attachment->id,
        );
    }

    public function test_rescan_command_can_dispatch_pending_and_failed_records(): void
    {
        $pending = $this->storeAttachment('pending.pdf', 'pending evidence');
        $failed = $this->storeAttachment('failed.pdf', 'failed evidence');
        $failed->update(['scan_status' => FinancialAttachment::SCAN_FAILED]);
        Queue::fake();

        $this->artisan('finance:scan-attachments', ['--include-failed' => true])
            ->expectsOutput('Dispatched 2 financial attachment scan(s).')
            ->assertExitCode(0);

        Queue::assertPushed(ScanFinancialAttachmentJob::class, 2);
        $this->assertSame(FinancialAttachment::SCAN_PENDING, $failed->fresh()->scan_status);
        $this->assertSame(FinancialAttachment::SCAN_PENDING, $pending->fresh()->scan_status);
    }

    public function test_clamav_adapter_fails_closed_when_the_binary_is_unavailable(): void
    {
        config()->set('finance.attachments.scanner.binary', 'missing-clamav-binary-for-test');

        try {
            app(ClamAvFinancialAttachmentScanner::class)->scan(__FILE__);
            $this->fail('A missing scanner binary must not mark a file as clean.');
        } catch (FinancialAttachmentScanException $exception) {
            $this->assertSame('scanner_unavailable', $exception->errorCode);
            $this->assertTrue($exception->retryable);
        }
    }

    public function test_unique_job_lock_uses_the_database_cache_store(): void
    {
        $job = new ScanFinancialAttachmentJob(123);

        $this->assertInstanceOf(DatabaseStore::class, $job->uniqueVia()->getStore());
        $this->assertLessThan($job->timeout, config('finance.attachments.scanner.timeout_seconds'));
        $this->assertLessThan(config('queue.connections.database.retry_after'), $job->timeout);
    }

    private function storeAttachment(string $name, string $contents): FinancialAttachment
    {
        return app(FinancialAttachmentService::class)->store(
            $this->expense,
            UploadedFile::fake()->createWithContent($name, $contents),
            $this->user,
            'invoice',
        );
    }

    private function bindScanner(FinancialAttachmentScanResult $result): void
    {
        $this->app->instance(FinancialAttachmentScanner::class, new class($result) implements FinancialAttachmentScanner
        {
            public function __construct(private readonly FinancialAttachmentScanResult $result) {}

            public function engine(): string
            {
                return $this->result->engine;
            }

            public function scan(string $absolutePath): FinancialAttachmentScanResult
            {
                return $this->result;
            }
        });
    }

    private function addMember(Business $business, User $user): void
    {
        BusinessUser::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }
}
