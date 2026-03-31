<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;

abstract class BasePortalController extends Controller
{
    protected function clientForUser(): ?Client
    {
        return Client::where('portal_user_id', auth()->id())->first();
    }
}
