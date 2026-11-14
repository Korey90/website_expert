<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutomationLogResource\Pages;
use App\Models\AutomationLog;
use App\Models\AutomationTrigger;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AutomationLogResource extends BaseResource
{
    protected static ?string $model = AutomationLog::class;

    protected static ?string $recordTitleAttribute = 'trigger_event';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static \UnitEnum|string|null $navigationGroup = 'Automation';

    protected static ?string $navigationLabel = 'Automation Logs';

    protected static ?int $navigationSort = 3;

    public static function getGloballySearchableAttributes(): array
    {
        return ['trigger_event', 'status', 'automationRule.name'];
    }

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
                            'failed' => 'danger',
                            'test' => 'info',
                            default => 'gray',
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
                    ->width('60px')
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('automationRule.name')
                    ->label('Rule')
                    ->searchable()
                    ->placeholder('—')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('trigger_event')
                    ->badge()
                    ->formatStateUsing(fn ($s) => AutomationTrigger::labelFor($s))
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'success',
                        'partial' => 'warning',
                        'failed' => 'danger',
                        'test' => 'info',
                    ]),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn ($state) => $state === 'test' ? 'gray' : 'primary'),
                Tables\Columns\TextColumn::make('lead.title')
                    ->label('Lead')
                    ->placeholder('—')
                    ->visibleFrom('lg')
                    ->url(fn (AutomationLog $record): ?string => $record->lead && LeadResource::canView($record->lead)
                        ? LeadResource::getUrl('view', ['record' => $record->lead])
                        : null),
                Tables\Columns\TextColumn::make('actions_count')
                    ->label('Actions')
                    ->getStateUsing(fn ($record) => count($record->actions_executed ?? []))
                    ->badge()
                    ->color('gray')
                    ->visibleFrom('xl'),
                Tables\Columns\TextColumn::make('executed_at')
                    ->label('Executed')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->defaultSort('executed_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'partial' => 'Partial',
                        'failed' => 'Failed',
                        'test' => 'Test',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'automation' => 'Automation',
                        'test' => 'Test Run',
                    ]),
                Tables\Filters\SelectFilter::make('trigger_event')
                    ->options(fn () => AutomationTrigger::getOptions())
                    ->label('Trigger Event'),
                Tables\Filters\Filter::make('executed_at')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $v) => $q->whereDate('executed_at', '>=', $v))
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
                ]),
            ])
            ->recordUrl(fn (AutomationLog $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading(__('admin.migration.automation.logs.empty_heading'))
            ->emptyStateDescription(__('admin.migration.automation.logs.empty_description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAutomationLogs::route('/'),
            'view' => Pages\ViewAutomationLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
