<?php

namespace Tests\Feature;

use App\Actions\Admin\CancelJobBatchAction;
use App\Actions\Admin\DeleteFailedJobAction;
use App\Actions\Admin\DeletePendingJobAction;
use App\Actions\Admin\FlushFailedJobsAction;
use App\Actions\Admin\RetryFailedJobAction;
use App\Models\FailedJob;
use App\Models\PendingJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class JobManagerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // FailedJob model helpers
    // -------------------------------------------------------------------------

    private function createFailedJob(array $overrides = []): FailedJob
    {
        return FailedJob::create(array_merge([
            'uuid'       => \Illuminate\Support\Str::uuid()->toString(),
            'connection' => 'database',
            'queue'      => 'default',
            'payload'    => json_encode(['displayName' => 'App\\Jobs\\TestJob', 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call']),
            'exception'  => 'RuntimeException: Something went wrong',
            'failed_at'  => now(),
        ], $overrides));
    }

    private function createPendingJob(array $overrides = []): PendingJob
    {
        return PendingJob::create(array_merge([
            'queue'        => 'default',
            'payload'      => json_encode(['displayName' => 'App\\Jobs\\TestJob']),
            'attempts'     => 0,
            'reserved_at'  => null,
            'available_at' => now()->timestamp,
            'created_at'   => now()->timestamp,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // FailedJob model
    // -------------------------------------------------------------------------

    public function test_failed_job_decodes_payload(): void
    {
        $job = $this->createFailedJob();

        $this->assertIsArray($job->decodedPayload());
        $this->assertSame('App\\Jobs\\TestJob', $job->jobClass());
    }

    public function test_failed_job_basename(): void
    {
        $job = $this->createFailedJob();

        $this->assertSame('TestJob', class_basename($job->jobClass()));
    }

    // -------------------------------------------------------------------------
    // PendingJob model
    // -------------------------------------------------------------------------

    public function test_pending_job_decodes_payload(): void
    {
        $job = $this->createPendingJob();

        $this->assertIsArray($job->decodedPayload());
        $this->assertSame('App\\Jobs\\TestJob', $job->jobClass());
    }

    public function test_pending_job_reserved_flag(): void
    {
        $waiting  = $this->createPendingJob(['reserved_at' => null]);
        $reserved = $this->createPendingJob(['reserved_at' => now()->timestamp]);

        $this->assertFalse($waiting->isReserved());
        $this->assertTrue($reserved->isReserved());
    }

    // -------------------------------------------------------------------------
    // DeleteFailedJobAction
    // -------------------------------------------------------------------------

    public function test_delete_failed_job_action_removes_record(): void
    {
        $job = $this->createFailedJob();

        $result = app(DeleteFailedJobAction::class)->execute($job->uuid);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $job->uuid]);
    }

    public function test_delete_failed_job_action_returns_false_for_missing_uuid(): void
    {
        $result = app(DeleteFailedJobAction::class)->execute('non-existent-uuid');

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // DeletePendingJobAction
    // -------------------------------------------------------------------------

    public function test_delete_pending_job_action_removes_record(): void
    {
        $job = $this->createPendingJob();

        $result = app(DeletePendingJobAction::class)->execute($job->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('jobs', ['id' => $job->id]);
    }

    // -------------------------------------------------------------------------
    // FlushFailedJobsAction
    // -------------------------------------------------------------------------

    public function test_flush_failed_jobs_action_calls_artisan(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:flush')
            ->andReturn(0);

        $result = app(FlushFailedJobsAction::class)->execute();

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // RetryFailedJobAction
    // -------------------------------------------------------------------------

    public function test_retry_failed_job_action_pushes_to_queue_and_removes_from_failed(): void
    {
        Queue::fake();

        $job = $this->createFailedJob();

        $result = app(RetryFailedJobAction::class)->execute($job->uuid);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $job->uuid]);
    }

    public function test_retry_failed_job_action_returns_false_for_missing_uuid(): void
    {
        Queue::fake();

        $result = app(RetryFailedJobAction::class)->execute('no-such-uuid');

        $this->assertFalse($result);
    }

    public function test_retry_all_retries_all_failed_jobs(): void
    {
        Queue::fake();

        $this->createFailedJob(['uuid' => 'uuid-a-' . uniqid()]);
        $this->createFailedJob(['uuid' => 'uuid-b-' . uniqid()]);

        $result = app(RetryFailedJobAction::class)->retryAll();

        $this->assertTrue($result);
        $this->assertDatabaseCount('failed_jobs', 0);
    }

    // -------------------------------------------------------------------------
    // CancelJobBatchAction — deleteBatch
    // -------------------------------------------------------------------------

    public function test_delete_batch_removes_record(): void
    {
        DB::table('job_batches')->insert([
            'id'             => 'test-batch-id',
            'name'           => 'Test Batch',
            'total_jobs'     => 5,
            'pending_jobs'   => 2,
            'failed_jobs'    => 0,
            'failed_job_ids' => '[]',
            'options'        => null,
            'cancelled_at'   => null,
            'created_at'     => now()->timestamp,
            'finished_at'    => null,
        ]);

        $result = app(CancelJobBatchAction::class)->deleteBatch('test-batch-id');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('job_batches', ['id' => 'test-batch-id']);
    }

    public function test_delete_batch_returns_false_for_missing_id(): void
    {
        $result = app(CancelJobBatchAction::class)->deleteBatch('no-such-batch');

        $this->assertFalse($result);
    }
}
