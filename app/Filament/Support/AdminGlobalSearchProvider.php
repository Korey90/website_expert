<?php

namespace App\Filament\Support;

use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Illuminate\Support\Str;

class AdminGlobalSearchProvider extends DefaultGlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        $results = parent::getResults($query) ?? GlobalSearchResults::make();
        $normalizedQuery = Str::lower(trim($query));

        if ($normalizedQuery === '') {
            return $results;
        }

        $navigationResults = collect(AdminNavigation::searchableDestinations())
            ->filter(fn (array $destination): bool => Str::contains(
                Str::lower($destination['label'].' '.$destination['section']),
                $normalizedQuery,
            ))
            ->take(8)
            ->map(fn (array $destination): GlobalSearchResult => new GlobalSearchResult(
                title: $destination['label'],
                url: $destination['url'],
                details: [__('admin.search.section') => $destination['section']],
            ));

        if ($navigationResults->isNotEmpty()) {
            $results->category(__('admin.search.navigation'), $navigationResults);
        }

        return $results;
    }
}
