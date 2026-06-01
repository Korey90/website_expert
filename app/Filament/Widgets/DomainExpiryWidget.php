<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DomainResource;
use App\Models\Domain;
use App\Scopes\BusinessScope;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DomainExpiryWidget extends BaseWidget
{
    protected static ?int $sort = 11;
    protected int|string|array $columnSpan = 'full';
    protected static bool $isLazy = true;
    protected static ?string $heading = 'Domains Expiring in 30 Days';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Domain::withoutGlobalScope(BusinessScope::class)
                    ->where('status', 'active')
                    ->whereBetween('expires_at', [now(), now()->addDays(30)])
                    ->orderBy('expires_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_domain')
                    ->label('Domain')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->getStateUsing(fn (Domain $record): int => (int) now()->diffInDays($record->expires_at, false))
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 7  => 'danger',
                        $state <= 14 => 'warning',
                        default      => 'info',
                    }),

                Tables\Columns\IconColumn::make('auto_renew')
                    ->label('Auto-Renew')
                    ->boolean(),

                Tables\Columns\TextColumn::make('business.company_name')
                    ->label('Business')
                    ->placeholder('—'),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn (Domain $record) => DomainResource::getUrl('view', ['record' => $record->id]))
                    ->icon('heroicon-m-eye')
                    ->label('View'),
            ]);
    }
}
