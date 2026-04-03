<?php

namespace App\Http\Middleware;

use App\Services\Leads\ApiTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuthentication
{
    public function __construct(private readonly ApiTokenService $tokenService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization', '');

        if (! str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized — missing Bearer token'], 401);
        }

        $plainToken = substr($authHeader, 7);

        // timing-safe: ApiToken::findByToken hashes before comparing
        $result = $this->tokenService->authenticate($plainToken);

        if (! $result) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        // Inject business into request attributes for downstream controllers
        $request->attributes->set('api_business', $result['business']);
        $request->attributes->set('api_token', $result['token']);

        return $next($request);
    }
}
