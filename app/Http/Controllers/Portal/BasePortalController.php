<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;

abstract class BasePortalController extends Controller
{
    protected function clientForUser(): ?Client
    {
        return Client::where('portal_user_id', auth()->id())->first();
    }

    protected function redirectWithoutWorkspace(string $message = 'Workspace access is required to continue.'): RedirectResponse
    {
        return redirect()
            ->route('portal.dashboard')
            ->with('error', $message);
    }
}
