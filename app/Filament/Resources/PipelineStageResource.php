<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PipelineStageResource\Pages;
use App\Models\PipelineStage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PipelineStageResource extends Resource
{
    protected static ?string $model = PipelineStage::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static \UnitEnum|string|null $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Pipeline Stages';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Stage Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(PipelineStage::class, 'slug', ignoreRecord: true)
                        ->helperText('Lowercase letters and hyphens only.'),
                    Forms\Components\ColorPicker::make('color')
                        ->default('#6B7280'),
                    Forms\Components\TextInput::make('order')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    Forms\Components\Textarea::make('description')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_won')
                        ->label('Marks lead as WON')
                        ->helperText('Leads in this stage are counted as closed-won.')
                        ->inline(false),
                    Forms\Components\Toggle::make('is_lost')
                        ->label('Marks lead as LOST')
                        ->helperText('Leads in this stage are counted as closed-lost.')
                        ->inline(false),
                ]),

            Section::make('Stage Checklist')
                ->description('Tasks that should be completed before moving the lead to the next stage. These appear as a live to-do on each lead\'s detail page.')
                ->schema([
                    Forms\Components\Repeater::make('checklist')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('label')
                                ->required()
                                ->placeholder('e.g. Send proposal to client')
                                ->columnSpan(2),
                            Forms\Components\Select::make('condition')
                                ->label('Auto-complete condition')
                                ->helperText('When this condition is met on the lead, the item is automatically marked done.')
                                ->placeholder('Manual only (no auto-detect)')
                                ->options([
                                    'has_assignee'     => '👤 Lead has an assigned user',
                                    'has_value'        => '💰 Deal value is set',
                                    'has_client'       => '🏢 Client is linked',
                                    'has_contact'      => '👥 Contact person is linked',
                                    'has_expected_close' => '📅 Expected close date is set',
                                    'has_phone'        => '📞 Client has a phone number',
                                    'has_email'        => '✉️ Client has an email address',
                                    'email_sent'       => '📤 At least one email has been sent',
                                    'has_project'      => '📁 Project has been created',
                                    'has_notes'        => '📝 At least one note exists',
                                    'has_calculator_data' => '🧮 Calculator data is present',
                                ])
                                ->columnSpan(1),
                        ])
                        ->columns(3)
                        ->addActionLabel('Add checklist item')
                        ->defaultItems(0)
                        ->reorderable()
                        ->collapsible()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')->label(''),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('slug')->sortable(),
                Tables\Columns\TextColumn::make('description')->limit(50)->placeholder('—'),
                Tables\Columns\TextColumn::make('order')->sortable()->label('Order'),
                Tables\Columns\IconColumn::make('is_won')->boolean()->label('Won'),
                Tables\Columns\IconColumn::make('is_lost')->boolean()->label('Lost'),
                Tables\Columns\TextColumn::make('leads_count')
                    ->counts('leads')
                    ->label('Leads')
                    ->badge(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->before(function (PipelineStage $record) {
                        // Reassign leads to the first remaining stage before deleting
                        $fallback = PipelineStage::where('id', '!=', $record->id)->orderBy('order')->first();
                        if ($fallback) {
                            $record->leads()->update(['pipeline_stage_id' => $fallback->id]);
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPipelineStages::route('/'),
            'create' => Pages\CreatePipelineStage::route('/create'),
            'view'   => Pages\ViewPipelineStage::route('/{record}'),
            'edit'   => Pages\EditPipelineStage::route('/{record}/edit'),
        ];
    }
}
