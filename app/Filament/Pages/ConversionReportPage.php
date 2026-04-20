<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConversionReportPage extends BasePage
{
    protected string $view = 'filament.pages.conversion-report';

    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-funnel';
    protected static \UnitEnum|string|null   $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Conversion Report';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $title           = 'Lead Conversion Report';
    protected static ?string $slug            = 'conversion-report';

    /** @return Collection<int, object> */
    public function getRows(): Collection
    {
        $rows = Lead::withTrashed()
            ->select([
                DB::raw("COALESCE(NULLIF(source, ''), 'unknown') as source"),
                DB::raw('COUNT(*) as total_leads'),
                DB::raw('SUM(CASE WHEN won_at IS NOT NULL THEN 1 ELSE 0 END) as converted'),
                DB::raw('SUM(CASE WHEN lost_at IS NOT NULL THEN 1 ELSE 0 END) as lost'),
                DB::raw('SUM(CASE WHEN won_at IS NULL AND lost_at IS NULL THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN won_at IS NOT NULL THEN COALESCE(value, 0) ELSE 0 END) as won_value'),
            ])
            ->groupByRaw("COALESCE(NULLIF(source, ''), 'unknown')")
            ->orderByDesc('total_leads')
            ->get();

        return $rows->map(function ($row) {
            $row->conversion_rate = $row->total_leads > 0
                ? round(($row->converted / $row->total_leads) * 100, 1)
                : 0;

            return $row;
        });
    }

    public function getTotals(): object
    {
        $totals = Lead::withTrashed()
            ->select([
                DB::raw('COUNT(*) as total_leads'),
                DB::raw('SUM(CASE WHEN won_at IS NOT NULL THEN 1 ELSE 0 END) as converted'),
                DB::raw('SUM(CASE WHEN lost_at IS NOT NULL THEN 1 ELSE 0 END) as lost'),
                DB::raw('SUM(CASE WHEN won_at IS NULL AND lost_at IS NULL THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN won_at IS NOT NULL THEN COALESCE(value, 0) ELSE 0 END) as won_value'),
            ])
            ->first();

        $totals->conversion_rate = $totals->total_leads > 0
            ? round(($totals->converted / $totals->total_leads) * 100, 1)
            : 0;

        return $totals;
    }
}
