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
