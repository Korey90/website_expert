<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pipeline_view')
                ->label('Pipeline View')
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->url(route('filament.admin.pages.pipeline-page')),
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }
}
