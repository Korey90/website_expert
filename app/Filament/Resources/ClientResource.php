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

