<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionResource extends BaseResource
{
    protected static ?string $model = Permission::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Permissions';

    protected static ?string $modelLabel = 'Permission';

    protected static ?string $pluralModelLabel = 'Permissions';

    protected static ?int $navigationSort = 3;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Permission name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->placeholder('e.g. view_clients'),
                Forms\Components\TextInput::make('guard_name')
                    ->label('Guard')
                    ->default('web')
                    ->required(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->columns(2)->schema([
                TextEntry::make('name')->label('Permission name'),
                TextEntry::make('guard_name')->label('Guard')->badge()->color('gray'),
                TextEntry::make('roles_count')
                    ->label('Used in roles')
                    ->getStateUsing(fn ($record) => $record->roles()->count())
                    ->badge()
                    ->color('primary'),
                TextEntry::make('created_at')->label('Created')->dateTime('d.m.Y H:i'),
            ]),
            Section::make('Roles using this permission')->schema([
                TextEntry::make('roles_list')
                    ->label('')
                    ->getStateUsing(fn ($record) => $record->roles()->pluck('name')->join(', ') ?: '—'),
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
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('gray')
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('roles_count')
                    ->label('Roles')
                    ->counts('roles')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d.m.Y')
                    ->sortable()
                    ->visibleFrom('xl')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn (Permission $record): ?string => static::canEdit($record)
                ? static::getUrl('edit', ['record' => $record])
                : null)
            ->emptyStateIcon('heroicon-o-key')
            ->emptyStateHeading(__('admin.migration.administration.permissions.empty_heading'))
            ->emptyStateDescription(__('admin.migration.administration.permissions.empty_description'))
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
