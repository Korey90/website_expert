<?php

namespace App\Http\Middleware;

use App\Models\LandingPage;
use App\Models\LandingPageGenerationVariant;
use App\Models\LandingPageSection;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLandingPageTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = currentBusiness();

        if ($business === null) {
            return $next($request);
        }

        foreach (['landingPage', 'section', 'variant'] as $parameter) {
            $resource = $request->route($parameter);

            if ($resource === null) {
                continue;
            }

            if (! $this->belongsToBusiness($resource, $business->id)) {
                abort(404);
            }
        }

        return $next($request);
    }

    private function belongsToBusiness(mixed $resource, string $businessId): bool
    {
        return match (true) {
            $resource instanceof LandingPage => $resource->business_id === $businessId,
            $resource instanceof LandingPageSection => $resource->landingPage?->business_id === $businessId,
            $resource instanceof LandingPageGenerationVariant => $resource->business_id === $businessId,
            default => true,
        };
    }
}