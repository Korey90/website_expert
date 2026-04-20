<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BriefingResource\Pages;
use App\Models\Briefing;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BriefingResource extends BaseResource
{
    protected static ?string $model = Briefing::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static \UnitEnum|string|null $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Briefings';

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
                        fn (Briefing $record): string => route('filament.admin.resources.leads.view', $record->lead_id)
                    ),

                Tables\Columns\TextColumn::make('conductedBy.name')
                    ->label('Conducted by')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'discovery'      => 'info',
                        'qualification'  => 'warning',
                        'proposal_input' => 'success',
                        'sales_offer'    => 'primary',
                        default          => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'       => 'gray',
                        'in_progress' => 'warning',
                        'completed'   => 'success',
                        'cancelled'   => 'danger',
                    }),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft'       => 'Draft',
                        'in_progress' => 'In Progress',
                        'completed'   => 'Completed',
                        'cancelled'   => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'discovery'      => 'Discovery',
                        'qualification'  => 'Qualification',
                        'proposal_input' => 'Proposal Input',
                        'sales_offer'    => 'Sales Offer',
                        'custom'         => 'Custom',
                    ]),
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
            'index' => Pages\ListBriefings::route('/'),
            'view'  => Pages\ViewBriefing::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->forBusiness();
    }
}
