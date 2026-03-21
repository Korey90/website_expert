<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteResource\Pages;
use App\Models\Client;
use App\Models\Quote;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static \UnitEnum|string|null $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Quote Details')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label('Quote No.')
                        ->default(fn () => 'QUOT-' . date('Y') . '-' . str_pad(Quote::count() + 1, 3, '0', STR_PAD_LEFT))
                        ->required(),
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->options(Client::pluck('company_name', 'id'))
                        ->searchable()->required(),
                    Forms\Components\Select::make('status')
                        ->options(['draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'expired' => 'Expired'])
                        ->default('draft')->required(),
                    Forms\Components\Select::make('currency')
                        ->options(['GBP' => '£ GBP', 'EUR' => '€ EUR', 'USD' => '$ USD'])
                        ->default('GBP')->required(),
                    Forms\Components\TextInput::make('vat_rate')->numeric()->default(20)->suffix('%'),
                    Forms\Components\DatePicker::make('valid_until')->default(today()->addDays(30)),
                ]),

            Section::make('Line Items')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('description')->required()->columnSpan(3),
                            Forms\Components\Textarea::make('details')->rows(2)->columnSpan(3),
                            Forms\Components\TextInput::make('quantity')->numeric()->default(1)->columnSpan(1),
                            Forms\Components\TextInput::make('unit_price')->numeric()->prefix('£')->columnSpan(2),
                        ])
                        ->columns(9)
                        ->defaultItems(1),
                ]),

            Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
            Forms\Components\Textarea::make('terms')->rows(3)->columnSpanFull()->default('This quote is valid for 30 days from the date of issue.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'sent' => 'info', 'accepted' => 'success', 'rejected' => 'danger', 'expired' => 'warning', default => 'gray' }),
                Tables\Columns\TextColumn::make('total')->money('GBP')->sortable(),
                Tables\Columns\TextColumn::make('valid_until')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'rejected' => 'Rejected']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'view'   => Pages\ViewQuote::route('/{record}'),
            'edit'   => Pages\EditQuote::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}

