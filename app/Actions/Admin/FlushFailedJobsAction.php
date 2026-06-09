<?php

namespace App\Actions\Admin;

use Illuminate\Support\Facades\Artisan;

class FlushFailedJobsAction
{
    public function execute(): bool
    {
        Artisan::call('queue:flush');

        return true;
    }
}
