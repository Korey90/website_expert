<?php

namespace App\Services\Seo;

use App\Data\Seo\SerpSearchResult;
use App\Exceptions\Seo\SerpProviderException;

interface SerpProviderInterface
{
    /**
     * Fetch organic Google results for a keyword/location/device combination.
     *
     * @throws SerpProviderException on timeout, HTTP error or unexpected payload.
     */
    public function searchOrganicResults(
        string $keyword,
        string $location,
        ?string $locationCode,
        string $device,
        string $language,
        int $topResults,
    ): SerpSearchResult;
}
