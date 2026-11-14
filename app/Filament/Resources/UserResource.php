<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends BaseResource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('phone')->maxLength(30),
                    Forms\Components\Select::make('locale')
                        ->options(['en' => 'English', 'pl' => 'Polish'])
                        ->default('en'),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->minLength(8)
                        ->dehydrateStateUsing(fn ($s) => filled($s) ? bcrypt($s) : null)
                        ->dehydrated(fn ($s) => filled($s))
                        ->required(fn (string $context) => $context === 'create'),
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\CheckboxList::make('roles')
                        ->relationship('roles', 'name')
                        ->columns(2)
                        ->columnSpanFull(),
                    Forms\Components\CheckboxList::make('businesses')
                        ->label('Business access')
                        ->relationship('businesses', 'name')
                        ->helperText('Required for tenant-scoped finance, accounting and UK tax data.')
                        ->columns(2)
                        ->bulkToggleable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->email),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->separator(',')
                    ->visibleFrom('md'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable()
                    ->visibleFrom('xl')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->recordUrl(fn (User $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading(__('admin.migration.administration.users.empty_heading'))
            ->emptyStateDescription(__('admin.migration.administration.users.empty_description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
