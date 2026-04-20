<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalClientAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $hasClientPortalAccess = Client::where('portal_user_id', $request->user()->id)->exists();

        if ($hasClientPortalAccess) {
            return $next($request);
        }

        return redirect()
            ->route('portal.dashboard')
            ->with('error', 'Client portal access is required to continue.');
    }
}