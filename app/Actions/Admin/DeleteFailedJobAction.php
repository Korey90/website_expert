<?php

namespace App\Actions\Admin;

use App\Models\FailedJob;

class DeleteFailedJobAction
{
    public function execute(string $uuid): bool
    {
        return (bool) FailedJob::where('uuid', $uuid)->delete();
    }
}
