<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectTemplateResource\Pages;
use App\Models\ProjectTemplate;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class ProjectTemplateResource extends Resource
{
    protected static ?string $model = ProjectTemplate::class;
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-rectangle-stack';
    protected static \UnitEnum|string|null   $navigationGroup = 'Projects';
    protected static ?string $navigationLabel = 'Project Templates';
    protected static ?int    $navigationSort  = 2;

    private static array $serviceTypes = [
        'wizytowka' => 'Business Card Site',
        'landing'   => 'Landing Page',
        'ecommerce' => 'E-Commerce',
        'aplikacja' => 'Web Application',
        'seo'       => 'SEO',
        'other'     => 'Other',
    ];

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Template Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Business Card Website')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('service_type')
                        ->options(self::$serviceTypes)
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->placeholder('Internal notes about when to use this template...')
                        ->columnSpanFull(),
                ]),

            Section::make('Project Phases')
                ->description('Phases will be auto-created (with status "pending") when this template is applied to a new project.')
                ->schema([
                    Forms\Components\Repeater::make('phases')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->placeholder('e.g. Discovery')
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('order')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->columnSpan(1),

                            Forms\Components\Textarea::make('description')
                                ->rows(2)
                                ->placeholder('Optional phase description...')
                                ->columnSpanFull(),

                            Forms\Components\Repeater::make('tasks')
                                ->label('Default Tasks')
                                ->helperText('These tasks will be automatically created when this phase is applied to a new project.')
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->required()
                                        ->placeholder('e.g. Gather client assets')
                                        ->columnSpan(3),

                                    Forms\Components\Select::make('priority')
                                        ->options([
                                            'low'    => 'Low',
                                            'medium' => 'Medium',
                                            'high'   => 'High',
                                            'urgent' => 'Urgent',
                                        ])
                                        ->default('medium')
                                        ->columnSpan(1),

                                    Forms\Components\Textarea::make('description')
                                        ->rows(2)
                                        ->placeholder('Optional task description...')
                                        ->columnSpanFull(),
                                ])
                                ->columns(4)
                                ->defaultItems(0)
                                ->addActionLabel('Add Task')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                ->columnSpanFull(),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->addActionLabel('Add Phase')
                        ->reorderable('order')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string =>
                            isset($state['order'], $state['name'])
                                ? $state['order'] . '. ' . $state['name'] . (! empty($state['tasks']) ? '  (' . count($state['tasks']) . ' tasks)' : '')
                                : ($state['name'] ?? null)
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('service_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::$serviceTypes[$state] ?? $state),

                Tables\Columns\TextColumn::make('phases')
                    ->label('Phases')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' phases' : '0 phases')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->label('Updated'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjectTemplates::route('/'),
            'create' => Pages\CreateProjectTemplate::route('/create'),
            'edit'   => Pages\EditProjectTemplate::route('/{record}/edit'),
        ];
    }
}
