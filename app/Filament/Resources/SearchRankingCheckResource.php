<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SearchRankingCheckResource\Pages\ListSearchRankingChecks;
use App\Models\SearchRankingCheck;
use App\Scopes\BusinessScope;
use App\Support\PermissionHelper;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only usage/cost visibility for the public Google Rank Checker tool
 * (plan §38: checks, provider requests, cache hits, errors, distinct users via IP hash).
 */
class SearchRankingCheckResource extends BaseResource
{
    protected static ?string $model = SearchRankingCheck::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static \UnitEnum|string|null $navigationGroup = 'SEO';

    protected static ?string $navigationLabel = 'Google Rank Checker';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('keyword')
                    ->label('Fraza')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('domain')
                    ->label('Domena')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokalizacja')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('device')
                    ->label('Urządzenie')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'found' => 'success',
                        'not_found' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('position')
                    ->label('Pozycja')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ranking_url')
                    ->label('URL')
                    ->limit(40)
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('provider')
                    ->label('Provider')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('from_cache')
                    ->label('Cache')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'found' => 'Found',
                        'not_found' => 'Not found',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('provider')
                    ->options(fn () => SearchRankingCheck::query()->distinct()->pluck('provider', 'provider')->toArray()),
                Tables\Filters\TernaryFilter::make('from_cache')
                    ->label('Z cache'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSearchRankingChecks::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return PermissionHelper::allows(auth()->user(), 'view_businesses')
            ? $query->withoutGlobalScope(BusinessScope::class)
            : $query;
    }
}
