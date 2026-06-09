<?php

namespace App\Actions\Admin;

use Illuminate\Bus\BatchRepository;
use Illuminate\Support\Facades\DB;

class CancelJobBatchAction
{
    public function __construct(private readonly BatchRepository $batchRepository) {}

    public function execute(string $batchId): bool
    {
        $batch = $this->batchRepository->find($batchId);

        if ($batch === null) {
            return false;
        }

        $batch->cancel();

        return true;
    }

    public function deleteBatch(string $batchId): bool
    {
        $deleted = DB::table('job_batches')->where('id', $batchId)->delete();

        return $deleted > 0;
    }
}
