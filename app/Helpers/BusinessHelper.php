<?php

use App\Models\Business;

if (! function_exists('currentBusiness')) {
    /**
     * Return the currently authenticated user's active Business.
     * Returns null if the user is not authenticated or has no business.
     */
    function currentBusiness(): ?Business
    {
        if (! auth()->check()) {
            return null;
        }

        return auth()->user()->currentBusiness();
    }
}

if (! function_exists('defaultBusiness')) {
    /**
     * Return the default agency Business regardless of authentication.
     * Used in public-facing contexts (contact forms, landing pages without LP
     * record, webhooks) where no user session exists.
     *
     * Returns the first active Business, which in a single-tenant setup is
     * always the agency itself.
     */
    function defaultBusiness(): ?Business
    {
        return Business::where('is_active', true)->first();
    }
}
