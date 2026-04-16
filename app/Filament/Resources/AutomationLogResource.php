<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutomationLogResource\Pages;
use App\Models\AutomationLog;
use App\Models\AutomationTrigger;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;

class AutomationLogResource extends Resource
{
    protected static ?string $model = AutomationLog::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Automation Logs';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Summary')
                ->columns(3)
                ->schema([
                    TextEntry::make('automationRule.name')->label('Rule')->placeholder('—'),
                    TextEntry::make('trigger_event')
                        ->badge()
                        ->formatStateUsing(fn ($state) => AutomationTrigger::labelFor($state)),
                    TextEntry::make('status')->badge()
                        ->color(fn ($state) => match ($state) {
                            'success' => 'success',
                            'partial' => 'warning',
                            'failed'  => 'danger',
                            'test'    => 'info',
                            default   => 'gray',
                        }),
                    TextEntry::make('source')->badge(),
                    TextEntry::make('executed_at')->dateTime('d M Y H:i:s')->label('Executed at'),
                    TextEntry::make('lead.title')->label('Lead')->placeholder('—'),
                ]),

            Section::make('Context (trigger data)')
                ->schema([
                    TextEntry::make('context')
                        ->label('')
                        ->fontFamily('mono')
                        ->getStateUsing(fn ($record) => json_encode($record->context ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            Section::make('Actions Executed')
                ->schema([
                    TextEntry::make('actions_executed')
                        ->label('')
                        ->fontFamily('mono')
                        ->getStateUsing(fn ($record) => json_encode($record->actions_executed ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),
                Tables\Columns\TextColumn::make('automationRule.name')
                    ->label('Rule')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('trigger_event')
                    ->badge()
                    ->formatStateUsing(fn ($s) => AutomationTrigger::labelFor($s))
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'success',
                        'partial' => 'warning',
                        'failed'  => 'danger',
                        'test'    => 'info',
                    ]),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn ($state) => $state === 'test' ? 'gray' : 'primary'),
                Tables\Columns\TextColumn::make('lead.title')
                    ->label('Lead')
                    ->placeholder('—')
                    ->url(fn ($record) => $record->lead_id
                        ? route('filament.admin.resources.leads.view', $record->lead_id)
                        : null),
                Tables\Columns\TextColumn::make('actions_count')
                    ->label('Actions')
                    ->getStateUsing(fn ($record) => count($record->actions_executed ?? []))
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('executed_at')
                    ->label('Executed')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('executed_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'partial' => 'Partial',
                        'failed'  => 'Failed',
                        'test'    => 'Test',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'automation' => 'Automation',
                        'test'       => 'Test Run',
                    ]),
                Tables\Filters\SelectFilter::make('trigger_event')
                    ->options(fn () => AutomationTrigger::getOptions())
                    ->label('Trigger Event'),
                Tables\Filters\Filter::make('executed_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'],  fn ($q, $v) => $q->whereDate('executed_at', '>=', $v))
                            ->when($data['until'], fn ($q, $v) => $q->whereDate('executed_at', '<=', $v));
                    }),
            ])
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\Action::make('delete_older_30')
                        ->label('Delete older than 30 days')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn () => AutomationLog::olderThanDays(30)->delete()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAutomationLogs::route('/'),
            'view'  => Pages\ViewAutomationLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
