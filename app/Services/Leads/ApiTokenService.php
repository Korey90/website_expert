<?php

namespace App\Services\Leads;

use App\Models\ApiToken;
use App\Models\Business;
use App\Models\User;
use Carbon\Carbon;

class ApiTokenService
{
    /**
     * Create a new API token for a business.
     * Returns the plain token ONCE — it is never stored in DB.
     *
     * @return array{token: string, model: ApiToken}
     */
    public function create(Business $business, string $name, User $createdBy, ?Carbon $expiresAt = null): array
    {
        $result = ApiToken::generateToken();

        $model = ApiToken::create([
            'business_id' => $business->id,
            'name'        => $name,
            'token_hash'  => $result['hash'],
            'is_active'   => true,
            'expires_at'  => $expiresAt,
            'created_by'  => $createdBy->id,
        ]);

        return [
            'token' => $result['plain'], // shown to user ONCE
            'model' => $model,
        ];
    }

    /**
     * Permanently revoke (deactivate) a token.
     */
    public function revoke(ApiToken $token): void
    {
        $token->update(['is_active' => false]);
    }

    /**
     * Authenticate a Bearer token.
     * Updates `last_used_at` on success.
     *
     * @return array{token: ApiToken, business: Business}|null
     */
    public function authenticate(string $plainToken): ?array
    {
        $token = ApiToken::findByToken($plainToken);

        if (! $token) {
            return null;
        }

        $token->update(['last_used_at' => now()]);

        return [
            'token'    => $token,
            'business' => $token->business,
        ];
    }
}
