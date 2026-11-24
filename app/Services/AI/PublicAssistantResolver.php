<?php

namespace App\Services\AI;

use App\Models\AiAssistant;
use App\Models\Business;
use App\Scopes\BusinessScope;

final class PublicAssistantResolver
{
    /**
     * Public visitor is never allowed to choose business_id or assistant_id.
     * The configured ULID must be verified server-side; never use defaultBusiness().
     */
    public function resolve(): ?AiAssistant
    {
        // Public collection of chat data requires an active retention policy.
        if (! config('ai_assistant.enabled') || ! config('ai_assistant.retention_enabled')) {
            return null;
        }

        if (config('ai_assistant.recaptcha_required') && blank(config('services.recaptcha.secret_key'))) {
            return null;
        }

        $businessId = config('ai_assistant.public_business_id');
        if (! is_string($businessId) || ! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $businessId)) {
            return null;
        }

        if (! Business::query()->whereKey($businessId)->where('is_active', true)->exists()) {
            return null;
        }

        return AiAssistant::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $businessId)->where('enabled', true)->first();
    }
}
