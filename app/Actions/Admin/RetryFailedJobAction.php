<?php

namespace App\Actions\Admin;

use Illuminate\Support\Facades\Queue;

class RetryFailedJobAction
{
    public function execute(string $uuid): bool
    {
        /** @var \Illuminate\Queue\Failed\FailedJobProviderInterface $failer */
        $failer = app('queue.failer');

        $job = $failer->find($uuid);

        if ($job === null) {
            return false;
        }

        $payload = json_decode($job->payload, true) ?? [];
        $payload['attempts'] = 0;

        Queue::connection($job->connection)->pushRaw(json_encode($payload), $job->queue);

        $failer->forget($uuid);

        return true;
    }

    public function retryAll(): bool
    {
        /** @var \Illuminate\Queue\Failed\FailedJobProviderInterface $failer */
        $failer = app('queue.failer');

        foreach ($failer->all() as $job) {
            $payload = json_decode($job->payload, true) ?? [];
            $payload['attempts'] = 0;

            Queue::connection($job->connection)->pushRaw(json_encode($payload), $job->queue);

            $failer->forget($job->id);
        }

        return true;
    }
}
