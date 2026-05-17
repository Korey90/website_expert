<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalendarEventResource\Pages;
use App\Models\CalendarEvent;
use App\Traits\BelongsToTenant;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

class CalendarEventResource extends BaseResource
{
    protected static ?string $model = CalendarEvent::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static \UnitEnum|string|null $navigationGroup = 'Productivity';
    protected static ?string $navigationLabel = 'Events';
    protected static ?string $label = 'Event';
    protected static ?string $pluralLabel = 'Calendar Events';
    protected static ?int $navigationSort = 1;

    // ── Form ──────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Starts at')
                    ->required()
                    ->native(false),

                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('Ends at')
                    ->native(false)
                    ->afterOrEqual('starts_at'),

                Forms\Components\Toggle::make('all_day')
                    ->label('All day')
                    ->default(false),

                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        'meeting'  => 'Meeting',
                        'call'     => 'Call',
                        'deadline' => 'Deadline',
                        'reminder' => 'Reminder',
                        'task'     => 'Task',
                    ])
                    ->default('meeting')
                    ->required()
                    ->selectablePlaceholder(false),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'done'      => 'Done',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('scheduled')
                    ->required()
                    ->selectablePlaceholder(false),

                Forms\Components\ColorPicker::make('color')
                    ->label('Custom color (optional)')
                    ->helperText('Leave empty to use the default type color'),
            ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('effective_color')
                    ->label('')
                    ->getStateUsing(fn (CalendarEvent $record) => $record->getEffectiveColor())
                    ->width('40px'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'info'    => 'meeting',
                        'success' => 'call',
                        'danger'  => 'deadline',
                        'warning' => 'reminder',
                        'primary' => 'task',
                    ]),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'scheduled',
                        'success' => 'done',
                        'gray'    => 'cancelled',
                    ]),

                Tables\Columns\IconColumn::make('google_event_id')
                    ->label('Google')
                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-minus-circle')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->tooltip(fn ($state) => $state ? 'Synced to Google Calendar' : 'Not synced'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'meeting'  => 'Meeting',
                        'call'     => 'Call',
                        'deadline' => 'Deadline',
                        'reminder' => 'Reminder',
                        'task'     => 'Task',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'done'      => 'Done',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('starts_at', 'desc');
    }

    // ── Pages ─────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCalendarEvents::route('/'),
            'create' => Pages\CreateCalendarEvent::route('/create'),
            'edit'   => Pages\EditCalendarEvent::route('/{record}/edit'),
        ];
    }
}
