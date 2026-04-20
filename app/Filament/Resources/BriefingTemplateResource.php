<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BriefingTemplateResource\Pages;
use App\Models\BriefingTemplate;
use App\Models\ServiceItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BriefingTemplateResource extends BaseResource
{
    protected static ?string $model = BriefingTemplate::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static \UnitEnum|string|null $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Briefing Templates';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Template Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('service_slug')
                        ->label('Service')
                        ->options(
                            fn () => ServiceItem::active()
                                ->orderBy('sort_order')
                                ->pluck('slug', 'slug')
                                ->mapWithKeys(fn ($slug) => [$slug => $slug])
                                ->toArray()
                        )
                        ->searchable()
                        ->nullable(),

                    Forms\Components\Select::make('type')
                        ->options([
                            'discovery'      => 'Discovery',
                            'qualification'  => 'Qualification',
                            'proposal_input' => 'Proposal Input',
                            'sales_offer'    => 'Sales Offer',
                            'custom'         => 'Custom',
                        ])
                        ->required()
                        ->default('discovery'),

                    Forms\Components\Select::make('language')
                        ->options(['en' => 'English', 'pl' => 'Polish', 'pt' => 'Portuguese'])
                        ->required()
                        ->default('en'),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Sections & Questions')
                ->description('Define sections and questions for this briefing template.')
                ->schema([
                    Forms\Components\Repeater::make('sections')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Section title')
                                ->required(),

                            Forms\Components\TextInput::make('key')
                                ->label('Section key (snake_case)')
                                ->required()
                                ->regex('/^[a-z_]+$/'),

                            Forms\Components\Repeater::make('questions')
                                ->schema([
                                    Forms\Components\TextInput::make('key')
                                        ->label('Question key')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),

                                    Forms\Components\TextInput::make('label')
                                        ->label('Question label')
                                        ->required(),

                                    Forms\Components\Select::make('type')
                                        ->label('Field type')
                                        ->options([
                                            'text'     => 'Text (single line)',
                                            'textarea' => 'Textarea (multi-line)',
                                            'select'   => 'Select (dropdown)',
                                            'boolean'  => 'Yes / No',
                                            'rating'   => 'Rating (1–5)',
                                        ])
                                        ->default('text')
                                        ->required(),

                                    Forms\Components\TextInput::make('placeholder')
                                        ->label('Placeholder (optional)'),

                                    Forms\Components\Toggle::make('required')
                                        ->label('Required')
                                        ->default(false)
                                        ->inline(false),
                                ])
                                ->columns(2)
                                ->addActionLabel('Add question')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null),
                        ])
                        ->addActionLabel('Add section')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('service_slug')
                    ->label('Service')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'discovery'      => 'info',
                        'qualification'  => 'warning',
                        'proposal_input' => 'success',
                        'sales_offer'    => 'primary',
                        default          => 'gray',
                    }),

                Tables\Columns\TextColumn::make('language')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->placeholder('Global')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'discovery'      => 'Discovery',
                        'qualification'  => 'Qualification',
                        'proposal_input' => 'Proposal Input',
                        'sales_offer'    => 'Sales Offer',
                        'custom'         => 'Custom',
                    ]),
                Tables\Filters\SelectFilter::make('language')
                    ->options(['en' => 'English', 'pl' => 'Polish', 'pt' => 'Portuguese']),
                Tables\Filters\SelectFilter::make('service_slug')
                    ->label('Service')
                    ->options(
                        fn () => ServiceItem::active()
                            ->orderBy('sort_order')
                            ->pluck('slug', 'slug')
                            ->toArray()
                    ),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('service_slug');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBriefingTemplates::route('/'),
            'create' => Pages\CreateBriefingTemplate::route('/create'),
            'view'   => Pages\ViewBriefingTemplate::route('/{record}'),
            'edit'   => Pages\EditBriefingTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->forBusiness();
    }
}
