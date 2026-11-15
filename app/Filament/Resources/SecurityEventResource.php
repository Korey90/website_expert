<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityEventResource\Pages\ListSecurityEvents;
use App\Models\SecurityEvent;
use App\Services\Security\AbuseIpdbService;
use App\Services\Security\Fail2banService;
use App\Support\PermissionHelper;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class SecurityEventResource extends BaseResource
{
    protected static ?string $model = SecurityEvent::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static \UnitEnum|string|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Security Events';

    protected static ?int $navigationSort = 99;

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('banned_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('ip')
                    ->label(__('admin.migration.administration.security.ip'))
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('action')
                    ->label(__('admin.migration.administration.security.status'))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'banned' => 'danger',
                        'unbanned' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'banned' => __('admin.migration.administration.security.banned'),
                        'unbanned' => __('admin.migration.administration.security.unbanned'),
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('attack_type')
                    ->label(__('admin.migration.administration.security.attack_type'))
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('country')
                    ->label(__('admin.migration.administration.security.location'))
                    ->formatStateUsing(fn ($state, $record) => implode(', ', array_filter([$state, $record->city])))
                    ->placeholder('—')
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('isp')
                    ->label(__('admin.migration.administration.security.isp'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('failures')
                    ->label(__('admin.migration.administration.security.attempts'))
                    ->numeric()
                    ->sortable()
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('jail')
                    ->label(__('admin.migration.administration.security.jail'))
                    ->badge()
                    ->color('gray')
                    ->visibleFrom('xl')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('reported_to_abuseipdb_at')
                    ->label(__('admin.migration.administration.security.reported'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->visibleFrom('lg'),

                Tables\Columns\TextColumn::make('banned_at')
                    ->label(__('admin.migration.administration.security.time'))
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->sortable()
                    ->visibleFrom('sm'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label(__('admin.migration.administration.security.status'))
                    ->options([
                        'banned' => __('admin.migration.administration.security.banned'),
                        'unbanned' => __('admin.migration.administration.security.unbanned'),
                    ]),
                Tables\Filters\SelectFilter::make('jail')
                    ->label(__('admin.migration.administration.security.jail'))
                    ->options(fn () => SecurityEvent::query()->distinct()->pluck('jail', 'jail')->toArray()),
                Tables\Filters\Filter::make('not_reported')
                    ->label(__('admin.migration.administration.security.not_reported'))
                    ->query(fn ($query) => $query->whereNull('reported_to_abuseipdb_at')->where('action', 'banned')),
            ])
            ->recordActions([
                Action::make('unban')
                    ->label(__('admin.migration.administration.security.unban'))
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (SecurityEvent $record) => $record->action === 'banned' && ! $record->isUnbanned()
                        && PermissionHelper::allows(auth()->user(), 'manage_security_events'))
                    ->requiresConfirmation()
                    ->action(function (SecurityEvent $record) {
                        abort_unless(PermissionHelper::allows(auth()->user(), 'manage_security_events'), 403);

                        $success = app(Fail2banService::class)->unban($record->ip, $record->jail);
                        if ($success) {
                            $record->update(['action' => 'unbanned', 'unbanned_at' => now()]);
                            Notification::make()->title(__('admin.migration.administration.security.unban_success'))->success()->send();
                        } else {
                            Notification::make()
                                ->title(__('admin.migration.administration.security.unban_failed'))
                                ->body(__('admin.migration.administration.security.unban_failed_description'))
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('report_abuseipdb')
                    ->label(__('admin.migration.administration.security.report'))
                    ->icon('heroicon-o-flag')
                    ->color('warning')
                    ->visible(fn (SecurityEvent $record) => ! $record->isReported()
                        && PermissionHelper::allows(auth()->user(), 'manage_security_events'))
                    ->requiresConfirmation()
                    ->action(function (SecurityEvent $record) {
                        abort_unless(PermissionHelper::allows(auth()->user(), 'manage_security_events'), 403);

                        $success = app(AbuseIpdbService::class)->report(
                            $record->ip,
                            $record->jail,
                            "IP {$record->ip} from {$record->country} ({$record->isp}) - {$record->attack_type} - {$record->failures} attempts"
                        );
                        if ($success) {
                            $record->update(['reported_to_abuseipdb_at' => now()]);
                            Notification::make()->title(__('admin.migration.administration.security.report_success'))->success()->send();
                        } else {
                            Notification::make()
                                ->title(__('admin.migration.administration.security.report_failed'))
                                ->body(__('admin.migration.administration.security.report_failed_description'))
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('ban_again')
                    ->label(__('admin.migration.administration.security.ban_again'))
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (SecurityEvent $record) => $record->isUnbanned()
                        && PermissionHelper::allows(auth()->user(), 'manage_security_events'))
                    ->requiresConfirmation()
                    ->action(function (SecurityEvent $record) {
                        abort_unless(PermissionHelper::allows(auth()->user(), 'manage_security_events'), 403);

                        $success = app(Fail2banService::class)->ban($record->ip, $record->jail);
                        if ($success) {
                            $record->update(['action' => 'banned', 'unbanned_at' => null, 'banned_at' => now()]);
                            Notification::make()->title(__('admin.migration.administration.security.ban_success'))->success()->send();
                        } else {
                            Notification::make()->title(__('admin.migration.administration.security.ban_failed'))->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_report_abuseipdb')
                        ->label(__('admin.migration.administration.security.report_selected'))
                        ->icon('heroicon-o-flag')
                        ->visible(fn () => PermissionHelper::allows(auth()->user(), 'manage_security_events'))
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            abort_unless(PermissionHelper::allows(auth()->user(), 'manage_security_events'), 403);

                            $service = app(AbuseIpdbService::class);
                            $reported = 0;
                            foreach ($records as $record) {
                                if ($record->isReported()) {
                                    continue;
                                }
                                if ($service->report($record->ip, $record->jail)) {
                                    $record->update(['reported_to_abuseipdb_at' => now()]);
                                    $reported++;
                                }
                            }
                            Notification::make()
                                ->title(trans_choice('admin.migration.administration.security.reported_count', $reported, ['count' => $reported]))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-shield-check')
            ->emptyStateHeading(__('admin.migration.administration.security.empty_heading'))
            ->emptyStateDescription(__('admin.migration.administration.security.empty_description'))
            ->searchPlaceholder(__('admin.migration.administration.security.search_placeholder'))
            ->striped()
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSecurityEvents::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
