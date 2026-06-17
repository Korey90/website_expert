<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Services\Currency\CurrencySummaryFormatter;
use Illuminate\Support\Collection;

class ConversionReportPage extends BasePage
{
    protected string $view = 'filament.pages.conversion-report';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-funnel';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Conversion Report';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Lead Conversion Report';

    protected static ?string $slug = 'conversion-report';

    /** @return Collection<int, object> */
    public function getRows(): Collection
    {
        $money = app(CurrencySummaryFormatter::class);

        return Lead::withTrashed()
            ->get(['source', 'won_at', 'lost_at', 'value', 'currency'])
            ->groupBy(fn (Lead $lead): string => $this->sourceKey($lead))
            ->map(function (Collection $leads, string $source) use ($money): object {
                $converted = $leads->whereNotNull('won_at')->count();
                $wonValue = $money->sumByCurrency($leads->whereNotNull('won_at'), 'value');

                return (object) [
                    'source' => $source,
                    'total_leads' => $leads->count(),
                    'converted' => $converted,
                    'lost' => $leads->whereNotNull('lost_at')->count(),
                    'in_progress' => $leads->filter(fn (Lead $lead): bool => $lead->won_at === null && $lead->lost_at === null)->count(),
                    'conversion_rate' => $leads->count() > 0 ? round(($converted / $leads->count()) * 100, 1) : 0,
                    'won_value' => $wonValue->all(),
                    'won_value_formatted' => $money->formatGrouped($wonValue),
                ];
            })
            ->sortByDesc('total_leads')
            ->values();
    }

    public function getTotals(): object
    {
        $money = app(CurrencySummaryFormatter::class);
        $leads = Lead::withTrashed()->get(['won_at', 'lost_at', 'value', 'currency']);
        $converted = $leads->whereNotNull('won_at')->count();
        $wonValue = $money->sumByCurrency($leads->whereNotNull('won_at'), 'value');

        return (object) [
            'total_leads' => $leads->count(),
            'converted' => $converted,
            'lost' => $leads->whereNotNull('lost_at')->count(),
            'in_progress' => $leads->filter(fn (Lead $lead): bool => $lead->won_at === null && $lead->lost_at === null)->count(),
            'conversion_rate' => $leads->count() > 0 ? round(($converted / $leads->count()) * 100, 1) : 0,
            'won_value' => $wonValue->all(),
            'won_value_formatted' => $money->formatGrouped($wonValue),
        ];
    }

    private function sourceKey(Lead $lead): string
    {
        $source = trim((string) $lead->source);

        return $source !== '' ? $source : 'unknown';
    }
}
