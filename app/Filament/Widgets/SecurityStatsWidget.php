<?php

namespace App\Filament\Widgets;

use App\Models\SecurityEvent;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecurityStatsWidget extends BaseWidget
{
    protected static ?int $sort = 10;
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $todayBanned = SecurityEvent::where('action', 'banned')
            ->whereDate('banned_at', today())
            ->count();

        $totalBanned = SecurityEvent::where('action', 'banned')->count();

        $topAttack = SecurityEvent::where('action', 'banned')
            ->selectRaw('attack_type, COUNT(*) as cnt')
            ->groupBy('attack_type')
            ->orderByDesc('cnt')
            ->first();

        $topCountry = SecurityEvent::where('action', 'banned')
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as cnt')
            ->groupBy('country')
            ->orderByDesc('cnt')
            ->first();

        $unreported = SecurityEvent::where('action', 'banned')
            ->whereNull('reported_to_abuseipdb_at')
            ->count();

        return [
            Stat::make('Dziś zablokowane ataki', $todayBanned)
                ->description("Łącznie: {$totalBanned} wszystkich czasów")
                ->icon('heroicon-o-shield-exclamation')
                ->color($todayBanned > 10 ? 'danger' : ($todayBanned > 0 ? 'warning' : 'success')),

            Stat::make('Najczęstszy typ ataku', $topAttack?->attack_type ?? '—')
                ->description($topAttack ? "{$topAttack->cnt}x" : 'Brak danych')
                ->icon('heroicon-o-bug-ant')
                ->color('warning'),

            Stat::make('Top kraj atakujący', $topCountry?->country ?? '—')
                ->description($topCountry ? "{$topCountry->cnt} ataków" : 'Brak danych')
                ->icon('heroicon-o-globe-alt')
                ->color('info'),

            Stat::make('Niezgłoszone do AbuseIPDB', $unreported)
                ->description('Kliknij "Security Events" aby zgłosić')
                ->icon('heroicon-o-flag')
                ->color($unreported > 0 ? 'warning' : 'success'),
        ];
    }
}
