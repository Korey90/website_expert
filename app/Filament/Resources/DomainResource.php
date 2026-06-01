<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DomainResource\Pages;
use App\Filament\Support\FilamentPermissionRegistry;
use App\Models\Domain;
use App\Scopes\BusinessScope;
use App\Support\PermissionHelper;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DomainResource extends BaseResource
{
    protected static ?string $model = Domain::class;
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-globe-alt';
    protected static \UnitEnum|string|null   $navigationGroup = 'Domains';
    protected static ?string $navigationLabel = 'Domains';
    protected static ?int    $navigationSort  = 3;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Domain Details')
                ->columns(3)
                ->schema([
                    TextEntry::make('full_domain')
                        ->label('Domain')
                        ->weight('bold')
                        ->size('lg')
                        ->columnSpan(2),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'active'      => 'success',
                            'pending'     => 'warning',
                            'expired'     => 'danger',
                            'transferred' => 'info',
                            'cancelled'   => 'gray',
                            default       => 'gray',
                        }),

                    TextEntry::make('provider')
                        ->label('Provider')
                        ->placeholder('—'),

                    TextEntry::make('provider_domain_id')
                        ->label('Provider Domain ID')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('registered_at')
                        ->label('Registered')
                        ->date()
                        ->placeholder('—'),

                    TextEntry::make('expires_at')
                        ->label('Expires')
                        ->date()
                        ->color(fn ($record) => $record?->isExpiringSoon(30) ? 'danger' : null)
                        ->placeholder('—'),

                    IconEntry::make('auto_renew')
                        ->label('Auto-Renew')
                        ->boolean(),

                    IconEntry::make('whois_privacy')
                        ->label('WHOIS Privacy')
                        ->boolean(),
                ]),

            Section::make('Nameservers')
                ->columns(1)
                ->schema([
                    TextEntry::make('nameservers')
                        ->label('')
                        ->formatStateUsing(fn ($state) => is_array($state)
                            ? implode("\n", $state)
                            : ($state ?? '—'))
                        ->prose()
                        ->placeholder('No nameservers set'),
                ]),

            Section::make('Client & Order')
                ->columns(2)
                ->schema([
                    TextEntry::make('client.company_name')
                        ->label('Client')
                        ->placeholder('—'),

                    TextEntry::make('client.primary_contact_email')
                        ->label('Client Email')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('domainOrder.id')
                        ->label('Order ID')
                        ->url(fn ($record) => $record?->domainOrder?->id
                            ? DomainOrderResource::getUrl('view', ['record' => $record->domainOrder->id])
                            : null)
                        ->placeholder('—'),

                    TextEntry::make('business.company_name')
                        ->label('Business (Tenant)')
                        ->placeholder('—'),
                ]),

            Section::make('Event Log')
                ->collapsed()
                ->schema([
                    RepeatableEntry::make('events')
                        ->label('')
                        ->schema([
                            TextEntry::make('type')
                                ->label('Type')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('description')
                                ->label('Description'),

                            TextEntry::make('user.name')
                                ->label('By')
                                ->placeholder('System'),

                            TextEntry::make('created_at')
                                ->label('Date')
                                ->dateTime('d M Y, H:i'),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'      => 'success',
                        'pending'     => 'warning',
                        'expired'     => 'danger',
                        'transferred' => 'info',
                        'cancelled'   => 'gray',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpiringSoon(30) ? 'danger' : null),

                Tables\Columns\IconColumn::make('auto_renew')
                    ->label('Auto-Renew')
                    ->boolean(),

                Tables\Columns\TextColumn::make('provider')
                    ->label('Provider')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('business.company_name')
                    ->label('Business')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('registered_at')
                    ->label('Registered')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'      => 'Active',
                        'pending'     => 'Pending',
                        'expired'     => 'Expired',
                        'transferred' => 'Transferred',
                        'cancelled'   => 'Cancelled',
                    ]),

                Tables\Filters\TernaryFilter::make('auto_renew')
                    ->label('Auto-Renew'),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDomains::route('/'),
            'view'  => Pages\ViewDomain::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        if (PermissionHelper::allows($user, FilamentPermissionRegistry::panelAccessPermission())) {
            return parent::getEloquentQuery()
                ->withoutGlobalScope(BusinessScope::class);
        }

        return parent::getEloquentQuery();
    }
}
