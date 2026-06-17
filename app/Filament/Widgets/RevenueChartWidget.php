<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Lead;
use App\Services\Currency\CurrencyResolver;
use Filament\Widgets\ChartWidget;

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

            return [
                'key' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'date' => $date->copy()->startOfMonth(),
            ];
        });

        $resolver = app(CurrencyResolver::class);
        $monthKeys = $months->pluck('key');
        $series = [];

        Invoice::where('status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $months->first()['date'])
            ->get(['currency', 'paid_at', 'total'])
            ->each(function (Invoice $invoice) use ($monthKeys, $resolver, &$series): void {
                $monthKey = $invoice->paid_at?->format('Y-m');

                if (! $monthKey || ! $monthKeys->contains($monthKey)) {
                    return;
                }

                $currency = $resolver->normalize($invoice->currency);
                $series[$currency][$monthKey] = (float) ($series[$currency][$monthKey] ?? 0) + (float) $invoice->total;
            });

        if ($series === []) {
            $series[$resolver->defaultCurrency()] = [];
        }

        $palette = [
            ['#22c55e', '#16a34a'],
            ['#3b82f6', '#2563eb'],
            ['#f97316', '#ea580c'],
            ['#8b5cf6', '#7c3aed'],
        ];

        $datasets = collect($series)
            ->sortKeys()
            ->map(function (array $totals, string $currency) use ($monthKeys, $palette): array {
                static $index = 0;
                [$background, $border] = $palette[$index % count($palette)];
                $index++;

                return [
                    'label' => 'Revenue '.$currency,
                    'data' => $monthKeys->map(fn (string $monthKey): float => (float) ($totals[$monthKey] ?? 0))->toArray(),
                    'backgroundColor' => $background,
                    'borderColor' => $border,
                    'borderWidth' => 1,
                ];
            })
            ->toArray();

        return [
            'datasets' => $datasets,
            'labels' => $months->pluck('label')->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value){ return value.toLocaleString(); }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => true],
            ],
        ];
    }
}
