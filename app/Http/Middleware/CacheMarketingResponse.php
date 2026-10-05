<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds a short browser-only (`private`) Cache-Control header to public marketing pages.
 *
 * Deliberately NOT `public`/shared (no CDN/reverse-proxy caching): these pages embed
 * per-request personalized data (currency via DetectGeoCurrency, session-based locale/nav)
 * in the Inertia page payload, so a shared cache could serve one visitor's currency/locale
 * to another. `private` only allows the requesting browser itself to reuse the response
 * (e.g. back/forward navigation), which carries no cross-visitor risk.
 */
class CacheMarketingResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $response->headers->set('Cache-Control', 'private, max-age=60, must-revalidate');
        }

        return $response;
    }
}
