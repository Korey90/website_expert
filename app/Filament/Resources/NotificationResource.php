<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\DatabaseNotification;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotificationResource extends BaseResource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $modelLabel = 'Notification';

    protected static ?string $pluralModelLabel = 'Notifications';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Send Notification')->schema([
                Forms\Components\Select::make('user_ids')
                    ->label('Recipients')
                    ->options(User::orderBy('name')->pluck('name', 'id'))
                    ->multiple()
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('body')
                    ->label('Message')
                    ->rows(3),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Notification Details')->columns(2)->schema([
                TextEntry::make('notifiable.name')
                    ->label('Recipient'),
                TextEntry::make('created_at')
                    ->label('Sent at')
                    ->dateTime('d.m.Y H:i:s'),
                TextEntry::make('data_title')
                    ->label('Title')
                    ->getStateUsing(fn ($record) => $record->data['title'] ?? '-'),
                TextEntry::make('data_icon')
                    ->label('Icon')
                    ->getStateUsing(fn ($record) => $record->data['icon'] ?? '-'),
                TextEntry::make('data_body')
                    ->label('Message')
                    ->getStateUsing(fn ($record) => $record->data['body'] ?? '—')
                    ->columnSpanFull(),
            ]),
            Section::make('Status')->columns(3)->schema([
                TextEntry::make('read_at')
                    ->label('Read at')
                    ->dateTime('d.m.Y H:i:s')
                    ->placeholder('Not read'),
                TextEntry::make('deleted_at')
                    ->label('Dismissed at')
                    ->dateTime('d.m.Y H:i:s')
                    ->placeholder('Not dismissed'),
                TextEntry::make('id')
                    ->label('ID')
                    ->copyable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('notifiable.name')
                    ->label('Recipient')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('data_title')
                    ->label('Title')
                    ->getStateUsing(fn ($record) => $record->data['title'] ?? '-')
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('data->title', 'like', "%{$search}%"))
                    ->limit(60),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('read_status')
                    ->label('Read')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->read_at ? 'Read' : 'Unread')
                    ->color(fn (string $state) => match ($state) {
                        'Read'   => 'success',
                        'Unread' => 'warning',
                        default  => 'gray',
                    }),
                Tables\Columns\TextColumn::make('dismiss_status')
                    ->label('Dismissed')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->deleted_at ? 'Dismissed' : 'Active')
                    ->color(fn (string $state) => match ($state) {
                        'Dismissed' => 'danger',
                        'Active'    => 'primary',
                        default     => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('unread')
                    ->label('Unread only')
                    ->query(fn (Builder $query) => $query->whereNull('read_at')),
            ])
            ->actions([
                ViewAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Dismiss selected'),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'view'   => Pages\ViewNotification::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('data->format', 'filament');
    }
}
