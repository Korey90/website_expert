<?php

namespace App\Jobs;

use App\Actions\AI\IndexKnowledgeVersionAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class IndexAiKnowledgeVersionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public readonly string $businessId, public readonly string $versionId) {}

    public function handle(IndexKnowledgeVersionAction $action): void
    {
        $action->execute($this->businessId, $this->versionId);
    }

    public function failed(?Throwable $exception): void
    {
        app(IndexKnowledgeVersionAction::class)->markFailed($this->businessId, $this->versionId);
    }
}
