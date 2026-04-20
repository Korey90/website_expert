<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Shows leads that have had no updates in over 7 days and are still open (not won/lost).
 * Helps the team identify leads that need follow-up.
 */
class StaleLeadsWidget extends BaseWidget
{
    protected static ?int $sort = 10;
    protected int|string|array $columnSpan = 'full';
    protected static bool $isLazy = true;
    protected static ?string $heading = 'Stale Leads — No Activity in 7+ Days';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lead::withoutTrashed()
                    ->whereNull('won_at')
                    ->whereNull('lost_at')
                    ->where('updated_at', '<', now()->subDays(7))
                    ->orderBy('updated_at')
                    ->limit(15)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Lead')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('stage.name')
                    ->label('Stage')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('value')
                    ->money('GBP')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->since()
                    ->color('danger'),
            ])
            ->actions([
                Action::make('follow-up')
                    ->label('Follow Up')
                    ->url(fn (Lead $record) => route('filament.admin.resources.leads.edit', $record))
                    ->icon('heroicon-m-arrow-right-circle')
                    ->color('warning'),
            ])
            ->emptyStateHeading('All leads are active')
            ->emptyStateDescription('No leads have been idle for more than 7 days.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
