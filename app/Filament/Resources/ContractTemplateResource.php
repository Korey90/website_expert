<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractTemplateResource\Pages;
use App\Models\ContractTemplate;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ContractTemplateResource extends Resource
{
    protected static ?string $model = ContractTemplate::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static \UnitEnum|string|null $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Contract Templates';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->schema([
            Section::make()
                ->columns(4)
                ->schema([
                    TextEntry::make('name')
                        ->label('Template Name')
                        ->weight('bold')
                        ->size('lg')
                        ->columnSpan(2),

                    TextEntry::make('type')
                        ->label('Type')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'web_development' => 'Web Development',
                            'maintenance'     => 'Maintenance',
                            default           => $state,
                        })
                        ->color(fn ($state) => match ($state) {
                            'web_development' => 'info',
                            'maintenance'     => 'warning',
                            default           => 'gray',
                        }),

                    IconEntry::make('is_active')
                        ->label('Active')
                        ->boolean()
                        ->trueIcon('heroicon-o-check-circle')
                        ->falseIcon('heroicon-o-x-circle')
                        ->trueColor('success')
                        ->falseColor('danger'),

                    TextEntry::make('language')
                        ->label('Language')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'en' => '🇬🇧 English',
                            'pl' => '🇵🇱 Polish',
                            'pt' => '🇵🇹 Portuguese',
                            default => strtoupper($state),
                        })
                        ->color('gray'),

                    TextEntry::make('created_at')
                        ->label('Created')
                        ->dateTime('d M Y, H:i')
                        ->since(),

                    TextEntry::make('updated_at')
                        ->label('Last Updated')
                        ->dateTime('d M Y, H:i')
                        ->since(),
                ]),

            Section::make('Contract Content Preview')
                ->description('Placeholders like {{legal.company_name}} will be replaced with actual values when used in a contract.')
                ->schema([
                    Html::make(fn ($record) =>
                        '<div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">'
                        . '<div class="flex items-center gap-2.5 px-4 py-2 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 font-mono text-[11px] text-gray-500 dark:text-gray-400">'
                        . '<span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span>'
                        . '<span class="w-2.5 h-2.5 rounded-full bg-yellow-400 inline-block"></span>'
                        . '<span class="w-2.5 h-2.5 rounded-full bg-green-400 inline-block"></span>'
                        . '<span class="ml-1">' . e($record->name) . ' — preview</span>'
                        . '</div>'
                        . '<div class="prose prose-sm dark:prose-invert max-w-none bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100" style="padding:32px 20px;max-height:70vh;overflow-y:auto;line-height:1.75;font-size:14px;">'
                        . $record->content
                        . '</div>'
                        . '</div>'
                    )->columnSpanFull(),
                ]),
        ]);
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Template Info')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpan(1),
                    Forms\Components\Select::make('type')
                        ->options([
                            'web_development' => 'Web Development Agreement',
                            'maintenance'     => 'Website Maintenance Agreement',
                        ])
                        ->required(),
                    Forms\Components\Select::make('language')
                        ->options([
                            'en' => '🇬🇧 English',
                            'pl' => '🇵🇱 Polish',
                            'pt' => '🇵🇹 Portuguese',
                        ])
                        ->required(),
                ]),

            Section::make('Content')
                ->schema([
                    Forms\Components\RichEditor::make('content')
                        ->label('')
                        ->columnSpanFull()
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('contract-templates')
                        ->helperText('Use {{legal.company_name}}, {{legal.company_email}}, {{legal.deposit_percent}}, {{legal.payment_terms_days}} etc. as dynamic placeholders.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'web_development' => 'Web Development',
                        'maintenance'     => 'Maintenance',
                        default           => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'web_development' => 'info',
                        'maintenance'     => 'warning',
                        default           => 'gray',
                    }),
                Tables\Columns\TextColumn::make('language')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'en' => '🇬🇧 EN',
                        'pl' => '🇵🇱 PL',
                        'pt' => '🇵🇹 PT',
                        default => strtoupper($state),
                    })
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'web_development' => 'Web Development',
                        'maintenance'     => 'Maintenance',
                    ]),
                Tables\Filters\SelectFilter::make('language')
                    ->options([
                        'en' => 'English',
                        'pl' => 'Polish',
                        'pt' => 'Portuguese',
                    ]),
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
            ->defaultSort('type')
            ->reorderable('id');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContractTemplates::route('/'),
            'create' => Pages\CreateContractTemplate::route('/create'),
            'view'   => Pages\ViewContractTemplate::route('/{record}'),
            'edit'   => Pages\EditContractTemplate::route('/{record}/edit'),
        ];
    }
}
