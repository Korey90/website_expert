<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityEventResource\Pages\ListSecurityEvents;
use App\Models\SecurityEvent;
use App\Services\Security\AbuseIpdbService;
use App\Services\Security\Fail2banService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SecurityEventResource extends Resource
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
                    ->label('IP')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('action')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'banned'   => 'danger',
                        'unbanned' => 'success',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'banned'   => 'Zbanowany',
                        'unbanned' => 'Odbanowany',
                        default    => $state,
                    }),

                Tables\Columns\TextColumn::make('attack_type')
                    ->label('Typ ataku')
                    ->badge()
                    ->color('warning')
                    ->searchable(),

                Tables\Columns\TextColumn::make('country')
                    ->label('Kraj')
                    ->formatStateUsing(fn ($state, $record) => implode(', ', array_filter([$state, $record->city])))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('isp')
                    ->label('ISP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('failures')
                    ->label('Prób')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jail')
                    ->label('Jail')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('reported_to_abuseipdb_at')
                    ->label('AbuseIPDB')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('banned_at')
                    ->label('Czas')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'banned'   => 'Zbanowany',
                        'unbanned' => 'Odbanowany',
                    ]),
                Tables\Filters\SelectFilter::make('jail')
                    ->options(fn () => SecurityEvent::query()->distinct()->pluck('jail', 'jail')->toArray()),
                Tables\Filters\Filter::make('not_reported')
                    ->label('Niezgłoszone do AbuseIPDB')
                    ->query(fn ($query) => $query->whereNull('reported_to_abuseipdb_at')->where('action', 'banned')),
            ])
            ->recordActions([
                Action::make('unban')
                    ->label('Odbanuj')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (SecurityEvent $record) => $record->action === 'banned' && ! $record->isUnbanned())
                    ->requiresConfirmation()
                    ->action(function (SecurityEvent $record) {
                        $success = app(Fail2banService::class)->unban($record->ip, $record->jail);
                        if ($success) {
                            $record->update(['action' => 'unbanned', 'unbanned_at' => now()]);
                            Notification::make()->title('IP odbanowane')->success()->send();
                        } else {
                            Notification::make()->title('Błąd odbanowania')->body('Sprawdź czy www-data ma sudo dla fail2ban-client.')->danger()->send();
                        }
                    }),

                Action::make('report_abuseipdb')
                    ->label('Zgłoś AbuseIPDB')
                    ->icon('heroicon-o-flag')
                    ->color('warning')
                    ->visible(fn (SecurityEvent $record) => ! $record->isReported())
                    ->requiresConfirmation()
                    ->action(function (SecurityEvent $record) {
                        $success = app(AbuseIpdbService::class)->report(
                            $record->ip,
                            $record->jail,
                            "IP {$record->ip} from {$record->country} ({$record->isp}) - {$record->attack_type} - {$record->failures} attempts"
                        );
                        if ($success) {
                            $record->update(['reported_to_abuseipdb_at' => now()]);
                            Notification::make()->title('Zgłoszono do AbuseIPDB')->success()->send();
                        } else {
                            Notification::make()->title('Błąd zgłoszenia')->body('Sprawdź klucz ABUSEIPDB_API_KEY w .env')->danger()->send();
                        }
                    }),

                Action::make('ban_again')
                    ->label('Zbanuj ponownie')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (SecurityEvent $record) => $record->isUnbanned())
                    ->requiresConfirmation()
                    ->action(function (SecurityEvent $record) {
                        $success = app(Fail2banService::class)->ban($record->ip, $record->jail);
                        if ($success) {
                            $record->update(['action' => 'banned', 'unbanned_at' => null, 'banned_at' => now()]);
                            Notification::make()->title('IP zbanowane ponownie')->success()->send();
                        } else {
                            Notification::make()->title('Błąd banowania')->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_report_abuseipdb')
                        ->label('Zgłoś zaznaczone do AbuseIPDB')
                        ->icon('heroicon-o-flag')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $service  = app(AbuseIpdbService::class);
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
                            Notification::make()->title("Zgłoszono {$reported} IP")->success()->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSecurityEvents::route('/'),
        ];
    }
}
