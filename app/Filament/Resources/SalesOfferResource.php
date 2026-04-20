<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOfferResource\Pages;
use App\Models\SalesOffer;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SalesOfferResource extends BaseResource
{
    protected static ?string $model = SalesOffer::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-paper-airplane';
    protected static \UnitEnum|string|null $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Sales Offers';

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('lead.title')
                    ->label('Lead')
                    ->searchable()
                    ->sortable()
                    ->url(
                        fn (SalesOffer $record): string => route('filament.admin.resources.leads.view', $record->lead_id)
                    ),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created by')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('language')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'      => 'gray',
                        'sent'       => 'info',
                        'viewed'     => 'warning',
                        'converted'  => 'success',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('viewed_at')
                    ->label('Viewed')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'sent'      => 'Sent',
                        'viewed'    => 'Viewed',
                        'converted' => 'Converted',
                    ]),
                Tables\Filters\SelectFilter::make('language')
                    ->options(['en' => 'English', 'pl' => 'Polish', 'pt' => 'Portuguese']),
            ])
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOffers::route('/'),
            'view'  => Pages\ViewSalesOffer::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->forBusiness();
    }
}
