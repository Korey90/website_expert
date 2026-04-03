<?php

namespace App\Events;

use App\Models\BusinessProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BusinessProfileUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BusinessProfile $profile,
    ) {}
}
