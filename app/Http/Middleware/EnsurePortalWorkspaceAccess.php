<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalWorkspaceAccess
{
    public function handle(Request $request, Closure $next, string $context = 'default'): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        if (currentBusiness() !== null) {
            return $next($request);
        }

        return redirect()
            ->route('portal.dashboard')
            ->with('error', $this->messageFor($context));
    }

    private function messageFor(string $context): string
    {
        return match ($context) {
            'billing' => 'Workspace access is required for billing and plan management.',
            'leads'   => 'Workspace access is required to view captured leads.',
            default   => 'Workspace access is required to continue.',
        };
    }
}