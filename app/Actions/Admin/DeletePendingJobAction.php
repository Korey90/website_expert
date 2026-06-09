<?php

namespace App\Actions\Admin;

use App\Models\PendingJob;

class DeletePendingJobAction
{
    public function execute(int $id): bool
    {
        return (bool) PendingJob::where('id', $id)->delete();
    }
}
