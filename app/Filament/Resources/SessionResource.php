<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SessionResource\Pages;
use App\Models\Session;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SessionResource extends BaseResource
{
    protected static ?string $model = Session::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Active Sessions';

    protected static ?string $modelLabel = 'Session';

    protected static ?string $pluralModelLabel = 'Sessions';

    // Sessions are read-only — no create / edit
    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_activity', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('— Guest —')
                    ->description(fn (Session $record): ?string => $record->user?->email),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray')
                    ->visibleFrom('md'),

                TextColumn::make('browser')
                    ->label('Browser')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Chrome' => 'info',
                        'Firefox' => 'warning',
                        'Safari' => 'primary',
                        'Edge' => 'success',
                        'Opera' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mobile' => 'warning',
                        'Tablet' => 'info',
                        default => 'gray',
                    })
                    ->visibleFrom('lg'),

                TextColumn::make('last_activity_at')
                    ->label('Last Active')
                    ->dateTime('Y-m-d H:i:s')
                    ->description(fn (Session $record): string => Carbon::createFromTimestamp($record->last_activity)->diffForHumans()
                    )
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('last_activity', $direction)
                    ),

                TextColumn::make('id')
                    ->label('Session ID')
                    ->limit(20)
                    ->copyable()
                    ->copyMessage('Session ID copied')
                    ->fontFamily('mono')
                    ->color('gray')
                    ->visibleFrom('xl')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_current')
                    ->label('Current')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->trueColor('success')
                    ->falseIcon('')
                    ->getStateUsing(fn (Session $record): bool => $record->is_current),
            ])
            ->filters([
                TernaryFilter::make('auth_type')
                    ->label('Session type')
                    ->placeholder('All sessions')
                    ->trueLabel('Authenticated users')
                    ->falseLabel('Guest sessions')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('user_id'),
                        false: fn (Builder $query) => $query->whereNull('user_id'),
                    ),

                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('active')
                    ->label('Active (last 2 hours)')
                    ->query(fn (Builder $query) => $query->where('last_activity', '>=', now()->subHours(2)->timestamp)
                    ),
            ])
            ->actions([
                Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Revoke session?')
                    ->modalDescription('The user will be immediately logged out of this session.')
                    ->modalSubmitActionLabel('Revoke')
                    ->visible(fn (Session $record): bool => static::canDelete($record))
                    ->action(function (Session $record): void {
                        abort_unless(static::canDelete($record), 403);

                        $record->delete();
                        Notification::make()
                            ->title('Session revoked')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('revoke_selected')
                        ->label('Revoke selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Revoke selected sessions?')
                        ->modalDescription('All selected users will be immediately logged out.')
                        ->modalSubmitActionLabel('Revoke')
                        ->visible(fn (): bool => static::canDeleteAny())
                        ->action(function (Collection $records): void {
                            abort_unless(static::canDeleteAny(), 403);

                            $records->each->delete();
                            Notification::make()
                                ->title('Sessions revoked')
                                ->body($records->count().' session(s) have been revoked.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-computer-desktop')
            ->emptyStateHeading(__('admin.migration.administration.sessions.empty_heading'))
            ->emptyStateDescription(__('admin.migration.administration.sessions.empty_description'))
            ->striped()
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user')
            ->orderBy('last_activity', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSessions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
