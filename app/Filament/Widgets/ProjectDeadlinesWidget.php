<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Shows projects with a deadline in the next 14 days or already overdue.
 * Color-coded: red = overdue, orange = ≤7 days, primary = 8–14 days.
 */
class ProjectDeadlinesWidget extends BaseWidget
{
    protected static ?int $sort = 9;
    protected int|string|array $columnSpan = 'full';
    protected static bool $isLazy = true;
    protected static ?string $heading = 'Upcoming & Overdue Deadlines';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::withoutTrashed()
                    ->whereIn('status', ['active', 'on_hold'])
                    ->whereNotNull('deadline')
                    ->where('deadline', '<=', now()->addDays(14))
                    ->orderBy('deadline')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'  => 'success',
                        'on_hold' => 'warning',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('deadline')
                    ->date('d M Y')
                    ->color(fn ($record) => match (true) {
                        $record->deadline < now()                => 'danger',
                        $record->deadline <= now()->addDays(7)   => 'warning',
                        default                                  => 'primary',
                    })
                    ->description(fn ($record) => match (true) {
                        $record->deadline < now()              => 'Overdue by ' . now()->diffInDays($record->deadline) . ' day(s)',
                        $record->deadline <= now()->addDays(7) => 'Due in ' . now()->diffInDays($record->deadline) . ' day(s)',
                        default                                => 'Due in ' . now()->diffInDays($record->deadline) . ' day(s)',
                    }),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('budget')
                    ->money('GBP')
                    ->placeholder('—'),
            ])
            ->actions([
                Action::make('edit')
                    ->url(fn (Project $record) => route('filament.admin.resources.projects.edit', $record))
                    ->icon('heroicon-m-pencil-square'),
            ])
            ->emptyStateHeading('No upcoming deadlines')
            ->emptyStateDescription('All projects are on track or have no deadline set within 14 days.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
