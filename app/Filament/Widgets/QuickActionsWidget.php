<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 0;
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-actions';

    public function getActions(): array
    {
        return [
            [
                'label'  => 'New Lead',
                'icon'   => 'heroicon-o-funnel',
                'url'    => '/admin/leads/create',
                'color'  => 'primary',
            ],
            [
                'label'  => 'New Client',
                'icon'   => 'heroicon-o-building-office',
                'url'    => '/admin/clients/create',
                'color'  => 'primary',
            ],
            [
                'label'  => 'New Project',
                'icon'   => 'heroicon-o-briefcase',
                'url'    => '/admin/projects/create',
                'color'  => 'primary',
            ],
            [
                'label'  => 'New Quote',
                'icon'   => 'heroicon-o-document-text',
                'url'    => '/admin/quotes/create',
                'color'  => 'primary',
            ],
            [
                'label'  => 'New Invoice',
                'icon'   => 'heroicon-o-banknotes',
                'url'    => '/admin/invoices/create',
                'color'  => 'primary',
            ],
            [
                'label'  => 'Pipeline',
                'icon'   => 'heroicon-o-viewfinder-circle',
                'url'    => '/admin/pipeline',
                'color'  => 'gray',
            ],
        ];
    }
}
