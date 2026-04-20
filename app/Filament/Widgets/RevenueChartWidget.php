<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Lead;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    public function getColumnSpan(): int|string|array
    {
        return Lead::whereNotNull('source')->exists() ? 1 : 2;
    }

    protected string $color = 'success';

    protected ?string $heading = 'Monthly Revenue';

    protected ?string $description = 'Paid invoices over the last 12 months';

    protected ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = collect(range(11, 0))->map(function (int $monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            $revenue = Invoice::where('status', 'paid')
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('total');

            return [
                'label'   => $date->format('M Y'),
                'revenue' => (float) $revenue,
            ];
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Revenue (£)',
                    'data'            => $months->pluck('revenue')->toArray(),
                    'backgroundColor' => '#22c55e',
                    'borderColor'     => '#16a34a',
                    'borderWidth'     => 1,
                ],
            ],
            'labels' => $months->pluck('label')->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => [
                        'callback' => "function(value){ return '£' + value.toLocaleString(); }",
                    ],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
