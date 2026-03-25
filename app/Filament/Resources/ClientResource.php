<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static \UnitEnum|string|null $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Dane firmy')
                ->columns(3)
                ->schema([
                    TextEntry::make('company_name')
                        ->label('Nazwa firmy')
                        ->weight('bold')
                        ->size('lg')
                        ->columnSpan(2),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'prospect' => 'gray',
                            'active'   => 'success',
                            'inactive' => 'warning',
                            'archived' => 'danger',
                            default    => 'gray',
                        }),

                    TextEntry::make('trading_name')
                        ->label('Nazwa handlowa')
                        ->placeholder('—'),

                    TextEntry::make('industry')
                        ->label('Branża')
                        ->placeholder('—'),

                    TextEntry::make('website')
                        ->label('Strona WWW')
                        ->url(fn ($state) => $state)
                        ->openUrlInNewTab()
                        ->placeholder('—'),

                    TextEntry::make('companies_house_number')
                        ->label('Companies House')
                        ->placeholder('—'),

                    TextEntry::make('vat_number')
                        ->label('NIP / VAT')
                        ->placeholder('—'),

                    TextEntry::make('source')
                        ->label('Źródło')
                        ->badge()
                        ->color('info')
                        ->placeholder('—'),
                ]),

            Section::make('Kontakt główny')
                ->columns(3)
                ->schema([
                    TextEntry::make('primary_contact_name')
                        ->label('Imię i nazwisko')
                        ->placeholder('—'),

                    TextEntry::make('primary_contact_email')
                        ->label('E-mail')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('primary_contact_phone')
                        ->label('Telefon')
                        ->copyable()
                        ->placeholder('—'),
                ]),

            Section::make('Adres')
                ->columns(3)
                ->collapsed()
                ->schema([
                    TextEntry::make('address_line1')->label('Ulica / linia 1')->placeholder('—'),
                    TextEntry::make('address_line2')->label('Ulica / linia 2')->placeholder('—'),
                    TextEntry::make('city')->label('Miasto')->placeholder('—'),
                    TextEntry::make('county')->label('Hrabstwo / powiat')->placeholder('—'),
                    TextEntry::make('postcode')->label('Kod pocztowy')->placeholder('—'),
                    TextEntry::make('country')->label('Kraj')->placeholder('—'),
                ]),

            Section::make('Finanse & Przypisanie')
                ->columns(3)
                ->schema([
                    TextEntry::make('lifetime_value')
                        ->label('Lifetime Value')
                        ->money('GBP')
                        ->placeholder('—'),

                    TextEntry::make('currency')
                        ->label('Waluta')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('assignedTo.name')
                        ->label('Opiekun')
                        ->placeholder('—'),
                ]),

            Section::make('Dostęp do portalu klienta')
                ->icon('heroicon-o-lock-closed')
                ->columns(3)
                ->schema([
                    IconEntry::make('portal_user_id')
                        ->label('Status konta')
                        ->boolean()
                        ->trueIcon('heroicon-o-check-circle')
                        ->falseIcon('heroicon-o-x-circle')
                        ->trueColor('success')
                        ->falseColor('danger')
                        ->getStateUsing(fn ($record) => (bool) $record->portal_user_id),

                    TextEntry::make('portalUser.email')
                        ->label('Login (e-mail)')
                        ->copyable()
                        ->placeholder('Brak konta')
                        ->icon('heroicon-m-envelope'),

                    TextEntry::make('portalUser.last_login_at')
                        ->label('Ostatnie logowanie')
                        ->dateTime('d M Y, H:i')
                        ->since()
                        ->placeholder('Nigdy'),
                ]),

            Section::make('Notatki')
                ->collapsed()
                ->schema([
                    TextEntry::make('notes')
                        ->label('')
                        ->prose()
                        ->columnSpanFull()
                        ->placeholder('Brak notatek.'),
                ]),

            Section::make('Communication Preferences')
                ->collapsed()
                ->columns(2)
                ->schema([
                    IconEntry::make('notify_email_transactional')
                        ->label('Transactional Emails')
                        ->boolean(),
                    IconEntry::make('notify_email_projects')
                        ->label('Project Update Emails')
                        ->boolean(),
                    IconEntry::make('notify_email_marketing')
                        ->label('Marketing / Automation Emails')
                        ->boolean(),
                    IconEntry::make('notify_sms')
                        ->label('SMS Notifications')
                        ->boolean(),
                    TextEntry::make('communication_prefs_updated_at')
                        ->label('Preferences Last Updated')
                        ->since()
                        ->placeholder('Never')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Company Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('company_name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('trading_name')->maxLength(255),
                    Forms\Components\TextInput::make('companies_house_number')->label('Companies House No.')->maxLength(20),
                    Forms\Components\TextInput::make('vat_number')->label('VAT Number')->maxLength(30),
                    Forms\Components\TextInput::make('website')->url()->maxLength(255),
                    Forms\Components\TextInput::make('industry')->maxLength(255),
                ]),

            Section::make('Status & Assignment')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options(['prospect' => 'Prospect', 'active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived'])
                        ->default('prospect')->required(),
                    Forms\Components\Select::make('source')
                        ->options(['website' => 'Website', 'referral' => 'Referral', 'cold_outreach' => 'Cold Outreach', 'social_media' => 'Social Media', 'google_ads' => 'Google Ads', 'other' => 'Other'])
                        ->default('website'),
                    Forms\Components\Select::make('assigned_to')
                        ->label('Assigned To')
                        ->options(User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))->pluck('name', 'id'))
                        ->searchable(),
                ]),

            Section::make('UK Address')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('address_line1')->label('Address Line 1'),
                    Forms\Components\TextInput::make('address_line2')->label('Address Line 2'),
                    Forms\Components\TextInput::make('city'),
                    Forms\Components\TextInput::make('county'),
                    Forms\Components\TextInput::make('postcode')->maxLength(20),
                    Forms\Components\Select::make('country')->options(['GB' => 'United Kingdom', 'IE' => 'Ireland', 'US' => 'United States'])->default('GB'),
                ]),

            Section::make('Primary Contact')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('primary_contact_name'),
                    Forms\Components\TextInput::make('primary_contact_email')->email(),
                    Forms\Components\TextInput::make('primary_contact_phone'),
                ]),

            Section::make('Financial')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('currency')->options(['GBP' => '£ GBP', 'EUR' => '€ EUR', 'USD' => '$ USD'])->default('GBP'),
                    Forms\Components\TextInput::make('lifetime_value')->label('Lifetime Value (£)')->numeric()->prefix('£')->disabled(),
                ]),

            Forms\Components\Textarea::make('notes')->columnSpanFull()->rows(3),

            Section::make('Communication Preferences')
                ->description('Manage client consent for emails and SMS notifications.')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('notify_email_transactional')
                        ->label('Transactional Emails')
                        ->helperText('Invoices, payment receipts, quotes, contracts.')
                        ->default(true),
                    Forms\Components\Toggle::make('notify_email_projects')
                        ->label('Project Update Emails')
                        ->helperText('Project status changes, messages.')
                        ->default(true),
                    Forms\Components\Toggle::make('notify_email_marketing')
                        ->label('Marketing / Automation Emails')
                        ->helperText('Automated campaign and marketing emails.')
                        ->default(true),
                    Forms\Components\Toggle::make('notify_sms')
                        ->label('SMS Notifications')
                        ->helperText('All SMS messages (payment receipts, reminders).')
                        ->default(true),
                    Forms\Components\Placeholder::make('communication_prefs_updated_at')
                        ->label('Preferences Last Updated')
                        ->content(fn ($record) => $record?->communication_prefs_updated_at?->diffForHumans() ?? 'Never')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('primary_contact_email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) { 'prospect' => 'gray', 'active' => 'success', 'inactive' => 'warning', 'archived' => 'danger', default => 'gray' }),
                Tables\Columns\TextColumn::make('city')->sortable(),
                Tables\Columns\TextColumn::make('lifetime_value')->label('LTV')->money('GBP')->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.name')->label('Assigned')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Added')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['prospect' => 'Prospect', 'active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view'   => Pages\ViewClient::route('/{record}'),
            'edit'   => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}

