<?php

namespace App\Filament\Resources\BriefingTemplateResource\Pages;

use App\Filament\Resources\BriefingTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBriefingTemplate extends ViewRecord
{
    protected static string $resource = BriefingTemplateResource::class;

    protected string $view = 'filament.resources.briefing-template-resource.pages.view-briefing-template';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => null)
                ->extraAttributes(['onclick' => 'window.print(); return false;']),

            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => route('briefing-template.pdf', $this->record))
                ->openUrlInNewTab(),

            EditAction::make(),
        ];
    }
}
