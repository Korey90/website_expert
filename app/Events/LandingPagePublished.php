<?php

namespace App\Events;

use App\Models\LandingPage;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LandingPagePublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly LandingPage $landingPage,
        public readonly User $publishedBy,
    ) {}
}
