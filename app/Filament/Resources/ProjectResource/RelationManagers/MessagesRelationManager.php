<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Client Messages';

    protected static \BackedEnum|string|null $icon = 'heroicon-o-chat-bubble-left-right';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Textarea::make('content')
                ->label('Message')
                ->required()
                ->rows(4)
                ->maxLength(5000)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->columns([
                Tables\Columns\TextColumn::make('sender_type')
                    ->label('From')
                    ->formatStateUsing(fn (string $state) => match (true) {
                        str_contains($state, 'User')   => '👤 Admin/Team',
                        str_contains($state, 'Client') => '🏢 Client',
                        default                        => $state,
                    })
                    ->badge()
                    ->color(fn (string $state) => str_contains($state, 'User') ? 'info' : 'warning'),

                Tables\Columns\TextColumn::make('content')
                    ->label('Message')
                    ->limit(120)
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('read_at')
                    ->label('Read')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->read_at !== null)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->defaultSort('created_at', 'asc')
            ->headerActions([
                CreateAction::make()
                    ->label('Send Message')
                    ->icon('heroicon-o-paper-airplane')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['sender_type'] = User::class;
                        $data['sender_id']   = auth()->id();
                        $data['read_at']     = now(); // admin messages auto-marked as read
                        return $data;
                    }),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
