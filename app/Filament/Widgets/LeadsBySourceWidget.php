<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class LeadsBySourceWidget extends ChartWidget
{
    protected static ?int $sort = 7;

    protected string $color = 'info';

    protected ?string $heading = 'Leads by Source';

    protected ?string $description = 'All-time lead distribution by source';

    protected ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $data = Lead::selectRaw('source, COUNT(*) as total')
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('total')
            ->pluck('total', 'source');

        $palette = [
            '#ff2b17', '#f97316', '#eab308', '#22c55e',
            '#06b6d4', '#6366f1', '#ec4899', '#84cc16',
        ];

        $colors = array_slice($palette, 0, $data->count());

        return [
            'datasets' => [
                [
                    'data'            => $data->values()->toArray(),
                    'backgroundColor' => $colors,
                    'borderWidth'     => 2,
                    'borderColor'     => '#fff',
                ],
            ],
            'labels' => $data->keys()->map(fn ($s) => ucfirst(str_replace('_', ' ', $s)))->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'right',
                ],
            ],
        ];
    }
}
