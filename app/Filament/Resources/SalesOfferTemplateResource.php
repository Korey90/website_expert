<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOfferTemplateResource\Pages;
use App\Models\SalesOfferTemplate;
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

class SalesOfferTemplateResource extends Resource
{
    protected static ?string $model = SalesOfferTemplate::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static \UnitEnum|string|null $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Offer Templates';

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

            Section::make('Offer Body')
                ->description('Use {{client_name}}, {{company_name}}, {{lead_title}} as placeholders.')
                ->schema([
                    Forms\Components\MarkdownEditor::make('body')
                        ->columnSpanFull()
                        ->toolbarButtons([
                            'bold', 'italic', 'heading', 'link',
                            'bulletList', 'orderedList', 'blockquote',
                            'codeBlock', 'undo', 'redo',
                        ]),
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

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
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
            'index'  => Pages\ListSalesOfferTemplates::route('/'),
            'create' => Pages\CreateSalesOfferTemplate::route('/create'),
            'view'   => Pages\ViewSalesOfferTemplate::route('/{record}'),
            'edit'   => Pages\EditSalesOfferTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (! auth()->user()?->hasRole('super_admin')) {
            $query->forBusiness();
        }

        return $query;
    }
}
