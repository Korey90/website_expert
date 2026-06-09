<?php

namespace App\Filament\Pages;

use App\Actions\Admin\CancelJobBatchAction;
use App\Actions\Admin\DeleteFailedJobAction;
use App\Actions\Admin\DeletePendingJobAction;
use App\Actions\Admin\FlushFailedJobsAction;
use App\Actions\Admin\RetryFailedJobAction;
use App\Models\FailedJob;
use App\Models\PendingJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class JobManagerPage extends BasePage
{
    protected string $view = 'filament.pages.job-manager';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-queue-list';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Job Manager';

    protected static ?int $navigationSort = 25;

    // --- State ---
    public string $activeTab = 'failed';

    public string $queueFilter = '';

    public string $search = '';

    public int $perPage = 20;

    public int $page = 1;

    // Modal state
    public bool $showPayloadModal = false;

    public string $modalTitle = '';

    public string $modalContent = '';

    public bool $showConfirmFlush = false;

    // -------------------------------------------------------------------------
    // Queue status summary
    // -------------------------------------------------------------------------

    /**
     * Returns a status snapshot used by the status bar in the view.
     *
     * @return array{
     *   total_pending: int,
     *   active: int,
     *   total_failed: int,
     *   worker_active: bool,
     *   last_failed_at: string|null,
     *   by_queue: array<string, array{pending: int, active: int, failed: int}>
     * }
     */
    public function queueStatus(): array
    {
        $pending = DB::table('jobs')
            ->select('queue', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) as active'))
            ->groupBy('queue')
            ->get();

        $failed = DB::table('failed_jobs')
            ->select('queue', DB::raw('COUNT(*) as total'))
            ->groupBy('queue')
            ->get()
            ->keyBy('queue');

        $byQueue = [];
        foreach ($pending as $row) {
            $byQueue[$row->queue] = [
                'pending' => (int) $row->total - (int) $row->active,
                'active'  => (int) $row->active,
                'failed'  => (int) ($failed->get($row->queue)?->total ?? 0),
            ];
        }

        // queues that only appear in failed_jobs (no pending)
        foreach ($failed as $queue => $row) {
            if (! isset($byQueue[$queue])) {
                $byQueue[$queue] = ['pending' => 0, 'active' => 0, 'failed' => (int) $row->total];
            }
        }

        $totalActive  = (int) DB::table('jobs')->whereNotNull('reserved_at')->count();
        $totalPending = (int) DB::table('jobs')->count();
        $totalFailed  = (int) DB::table('failed_jobs')->count();

        // A worker is "likely active" if there is any reserved job OR a job
        // was reserved within the last 2 minutes (worker finished and released it).
        $workerActive = $totalActive > 0
            || DB::table('jobs')->where('reserved_at', '>=', now()->subMinutes(2)->timestamp)->exists();

        $lastFailed = DB::table('failed_jobs')->max('failed_at');

        return [
            'total_pending'  => $totalPending,
            'active'         => $totalActive,
            'total_failed'   => $totalFailed,
            'worker_active'  => $workerActive,
            'last_failed_at' => $lastFailed,
            'by_queue'       => ksort($byQueue) ? $byQueue : $byQueue,
        ];
    }

    // -------------------------------------------------------------------------
    // Tab switching
    // -------------------------------------------------------------------------

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->page = 1;
        $this->queueFilter = '';
        $this->search = '';
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedQueueFilter(): void
    {
        $this->page = 1;
    }

    // -------------------------------------------------------------------------
    // Data — Failed Jobs
    // -------------------------------------------------------------------------

    public function failedJobs(): LengthAwarePaginator
    {
        return FailedJob::query()
            ->when($this->queueFilter !== '', fn ($q) => $q->where('queue', $this->queueFilter))
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $q->where('payload', 'like', '%'.$this->search.'%')
                    ->orWhere('exception', 'like', '%'.$this->search.'%')
                    ->orWhere('uuid', 'like', '%'.$this->search.'%');
            }))
            ->orderByDesc('failed_at')
            ->paginate($this->perPage, ['*'], 'failedPage', $this->page);
    }

    public function failedJobQueues(): array
    {
        return FailedJob::query()
            ->select('queue')
            ->distinct()
            ->orderBy('queue')
            ->pluck('queue')
            ->all();
    }

    public function failedJobsCount(): int
    {
        return FailedJob::count();
    }

    // -------------------------------------------------------------------------
    // Data — Pending Jobs
    // -------------------------------------------------------------------------

    public function pendingJobs(): LengthAwarePaginator
    {
        return PendingJob::query()
            ->when($this->queueFilter !== '', fn ($q) => $q->where('queue', $this->queueFilter))
            ->when($this->search !== '', fn ($q) => $q->where('payload', 'like', '%'.$this->search.'%'))
            ->orderByDesc('id')
            ->paginate($this->perPage, ['*'], 'pendingPage', $this->page);
    }

    public function pendingJobQueues(): array
    {
        return PendingJob::query()
            ->select('queue')
            ->distinct()
            ->orderBy('queue')
            ->pluck('queue')
            ->all();
    }

    public function pendingJobsCount(): int
    {
        return PendingJob::count();
    }

    // -------------------------------------------------------------------------
    // Data — Job Batches
    // -------------------------------------------------------------------------

    public function jobBatches(): LengthAwarePaginator
    {
        return DB::table('job_batches')
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderByDesc('created_at')
            ->paginate($this->perPage, ['*'], 'batchPage', $this->page);
    }

    public function jobBatchesCount(): int
    {
        return DB::table('job_batches')->count();
    }

    // -------------------------------------------------------------------------
    // Modal helpers
    // -------------------------------------------------------------------------

    public function viewFailedJobPayload(int $id): void
    {
        $job = FailedJob::findOrFail($id);
        $decoded = json_decode($job->payload, true);
        $this->modalContent = $decoded !== null
            ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : $job->payload;
        $this->modalTitle = 'Payload #' . $id;
        $this->showPayloadModal = true;
    }

    public function viewFailedJobException(int $id): void
    {
        $job = FailedJob::findOrFail($id);
        $this->modalContent = $job->exception;
        $this->modalTitle = __('jobs.exception') . ' #' . $id;
        $this->showPayloadModal = true;
    }

    public function viewPendingJobPayload(int $id): void
    {
        $job = PendingJob::findOrFail($id);
        $decoded = json_decode($job->payload, true);
        $this->modalContent = $decoded !== null
            ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : $job->payload;
        $this->modalTitle = 'Payload #' . $id;
        $this->showPayloadModal = true;
    }

    public function closeModal(): void
    {
        $this->showPayloadModal = false;
        $this->modalTitle = '';
        $this->modalContent = '';
    }

    // -------------------------------------------------------------------------
    // Actions — Failed Jobs
    // -------------------------------------------------------------------------

    public function retryJob(string $uuid): void
    {
        app(RetryFailedJobAction::class)->execute($uuid);

        Notification::make()
            ->title(__('jobs.retry_success'))
            ->success()
            ->send();
    }

    public function retryAll(): void
    {
        app(RetryFailedJobAction::class)->retryAll();

        Notification::make()
            ->title(__('jobs.retry_all_success'))
            ->success()
            ->send();
    }

    public function deleteFailedJob(string $uuid): void
    {
        app(DeleteFailedJobAction::class)->execute($uuid);

        Notification::make()
            ->title(__('jobs.delete_success'))
            ->success()
            ->send();
    }

    public function confirmFlush(): void
    {
        $this->showConfirmFlush = true;
    }

    public function cancelFlush(): void
    {
        $this->showConfirmFlush = false;
    }

    public function flushAllFailed(): void
    {
        app(FlushFailedJobsAction::class)->execute();

        $this->showConfirmFlush = false;

        Notification::make()
            ->title(__('jobs.flush_success'))
            ->success()
            ->send();
    }

    // -------------------------------------------------------------------------
    // Actions — Pending Jobs
    // -------------------------------------------------------------------------

    public function deletePendingJob(int $id): void
    {
        app(DeletePendingJobAction::class)->execute($id);

        Notification::make()
            ->title(__('jobs.delete_success'))
            ->success()
            ->send();
    }

    // -------------------------------------------------------------------------
    // Actions — Batches
    // -------------------------------------------------------------------------

    public function cancelBatch(string $batchId): void
    {
        $result = app(CancelJobBatchAction::class)->execute($batchId);

        if ($result) {
            Notification::make()
                ->title(__('jobs.batch_cancelled'))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('jobs.batch_not_found'))
                ->warning()
                ->send();
        }
    }

    public function deleteBatch(string $batchId): void
    {
        app(CancelJobBatchAction::class)->deleteBatch($batchId);

        Notification::make()
            ->title(__('jobs.delete_success'))
            ->success()
            ->send();
    }

    // -------------------------------------------------------------------------
    // Pagination helpers
    // -------------------------------------------------------------------------

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextPage(int $lastPage): void
    {
        if ($this->page < $lastPage) {
            $this->page++;
        }
    }

    // -------------------------------------------------------------------------
    // Restart workers
    // -------------------------------------------------------------------------

    public function restartWorkers(): void
    {
        Artisan::call('queue:restart');

        Notification::make()
            ->title(__('jobs.restart_success'))
            ->body(__('jobs.restart_body'))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('restartWorkers')
                ->label(__('jobs.restart_workers'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('jobs.restart_workers'))
                ->modalDescription(__('jobs.restart_confirm_body'))
                ->modalSubmitActionLabel(__('jobs.restart_workers'))
                ->action(fn () => $this->restartWorkers()),
        ];
    }
}
