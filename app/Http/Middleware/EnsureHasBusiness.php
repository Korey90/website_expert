<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasBusiness
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        if (currentBusiness() !== null) {
            return $next($request);
        }

        return redirect('/onboarding')->with(
            'warning',
            __('business.onboarding_required')
        );
    }
}
