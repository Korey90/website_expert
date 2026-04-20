<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActiveProjectsWidget extends BaseWidget
{
    protected static bool $isDiscoverable = false;
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Active Projects';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::withoutTrashed()
                    ->whereIn('status', ['active', 'on_hold'])
                    ->orderBy('deadline')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'active'  => 'success',
                        'on_hold' => 'warning',
                        default   => 'gray',
                    }),
                Tables\Columns\TextColumn::make('service_type')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('budget')
                    ->money('GBP'),
                Tables\Columns\TextColumn::make('deadline')
                    ->date()
                    ->color(fn ($record) => $record->deadline && $record->deadline < now() ? 'danger' : null),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Developer'),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn (Project $record) => route('filament.admin.resources.projects.edit', $record))
                    ->icon('heroicon-m-pencil-square'),
            ]);
    }
}
