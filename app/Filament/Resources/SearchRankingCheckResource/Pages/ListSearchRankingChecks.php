<?php

namespace App\Filament\Resources\SearchRankingCheckResource\Pages;

use App\Filament\Resources\SearchRankingCheckResource;
use Filament\Resources\Pages\ListRecords;

class ListSearchRankingChecks extends ListRecords
{
    protected static string $resource = SearchRankingCheckResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.rankings.list_subheading');
    }
}
