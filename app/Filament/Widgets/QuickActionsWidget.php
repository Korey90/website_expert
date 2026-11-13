<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\PipelinePage;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\QuoteResource;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-actions';

    public static function canView(): bool
    {
        return LeadResource::canCreate()
            || ClientResource::canCreate()
            || ProjectResource::canCreate()
            || QuoteResource::canCreate()
            || InvoiceResource::canCreate()
            || PipelinePage::canAccess();
    }

    public function getActions(): array
    {
        return array_values(array_filter([
            LeadResource::canCreate() ? [
                'label' => 'New Lead',
                'icon' => 'heroicon-o-funnel',
                'url' => LeadResource::getUrl('create'),
                'color' => 'primary',
            ] : null,
            ClientResource::canCreate() ? [
                'label' => 'New Client',
                'icon' => 'heroicon-o-building-office',
                'url' => ClientResource::getUrl('create'),
                'color' => 'primary',
            ] : null,
            ProjectResource::canCreate() ? [
                'label' => 'New Project',
                'icon' => 'heroicon-o-briefcase',
                'url' => ProjectResource::getUrl('create'),
                'color' => 'primary',
            ] : null,
            QuoteResource::canCreate() ? [
                'label' => 'New Quote',
                'icon' => 'heroicon-o-document-text',
                'url' => QuoteResource::getUrl('create'),
                'color' => 'primary',
            ] : null,
            InvoiceResource::canCreate() ? [
                'label' => 'New Invoice',
                'icon' => 'heroicon-o-banknotes',
                'url' => InvoiceResource::getUrl('create'),
                'color' => 'primary',
            ] : null,
            PipelinePage::canAccess() ? [
                'label' => 'Pipeline',
                'icon' => 'heroicon-o-viewfinder-circle',
                'url' => PipelinePage::getUrl(),
                'color' => 'gray',
            ] : null,
        ]));
    }
}
