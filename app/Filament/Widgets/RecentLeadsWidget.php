<?php

namespace App\Filament\Widgets;

use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\Lead;
use App\Support\PermissionHelper;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentLeadsWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return PermissionHelper::allows(auth()->user(), 'view_leads');
    }

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = true;

    protected static ?string $heading = 'Recent Leads';

    public function table(Table $table): Table
    {
        return $table
            ->query(Lead::withoutTrashed()->latest()->limit(8))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Lead')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client'),
                Tables\Columns\TextColumn::make('stage.name')
                    ->label('Stage')
                    ->badge(),
                Tables\Columns\TextColumn::make('value')
                    ->money(fn (Lead $record) => FilamentCurrency::tableCurrency($record)),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->since(),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn (Lead $record) => route('filament.admin.resources.leads.edit', $record))
                    ->icon('heroicon-m-pencil-square'),
            ]);
    }
}
