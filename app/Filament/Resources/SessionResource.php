<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SessionResource\Pages;
use App\Models\Session;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SessionResource extends Resource
{
    protected static ?string $model = Session::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-computer-desktop';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 2;
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
                    ->color('gray'),

                TextColumn::make('browser')
                    ->label('Browser')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Chrome'  => 'info',
                        'Firefox' => 'warning',
                        'Safari'  => 'primary',
                        'Edge'    => 'success',
                        'Opera'   => 'danger',
                        default   => 'gray',
                    }),

                TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mobile'  => 'warning',
                        'Tablet'  => 'info',
                        default   => 'gray',
                    }),

                TextColumn::make('last_activity_at')
                    ->label('Last Active')
                    ->dateTime('Y-m-d H:i:s')
                    ->description(fn (Session $record): string =>
                        Carbon::createFromTimestamp($record->last_activity)->diffForHumans()
                    )
                    ->sortable(query: fn (Builder $query, string $direction) =>
                        $query->orderBy('last_activity', $direction)
                    ),

                TextColumn::make('id')
                    ->label('Session ID')
                    ->limit(20)
                    ->copyable()
                    ->copyMessage('Session ID copied')
                    ->fontFamily('mono')
                    ->color('gray'),

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
                        true:  fn (Builder $query) => $query->whereNotNull('user_id'),
                        false: fn (Builder $query) => $query->whereNull('user_id'),
                    ),

                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('active')
                    ->label('Active (last 2 hours)')
                    ->query(fn (Builder $query) =>
                        $query->where('last_activity', '>=', now()->subHours(2)->timestamp)
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
                    ->action(function (Session $record): void {
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
                        ->action(function (Collection $records): void {
                            $records->each->delete();
                            Notification::make()
                                ->title('Sessions revoked')
                                ->body($records->count() . ' session(s) have been revoked.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('revoke_guest')
                        ->label('Revoke all guest sessions')
                        ->icon('heroicon-o-trash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Revoke all guest sessions?')
                        ->modalSubmitActionLabel('Revoke all guests')
                        ->action(function (): void {
                            $count = Session::whereNull('user_id')->count();
                            Session::whereNull('user_id')->delete();
                            Notification::make()
                                ->title('Guest sessions cleared')
                                ->body("{$count} guest session(s) removed.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
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
