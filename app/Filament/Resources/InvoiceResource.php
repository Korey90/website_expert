<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static \UnitEnum|string|null $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Invoice Details')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label('Invoice No.')
                        ->default(fn () => 'INV-' . date('Y') . '-' . str_pad(Invoice::count() + 1, 3, '0', STR_PAD_LEFT))
                        ->required(),
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->options(Client::pluck('company_name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->options(Project::pluck('title', 'id'))
                        ->searchable(),
                    Forms\Components\Select::make('status')
                        ->options(['draft' => 'Draft', 'sent' => 'Sent', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled'])
                        ->default('draft')->required(),
                    Forms\Components\Select::make('currency')
                        ->options(['GBP' => '£ GBP', 'EUR' => '€ EUR', 'USD' => '$ USD'])
                        ->default('GBP')->required(),
                    Forms\Components\TextInput::make('vat_rate')->numeric()->default(20)->suffix('%'),
                    Forms\Components\DatePicker::make('issue_date')->default(today())->required(),
                    Forms\Components\DatePicker::make('due_date')->default(today()->addDays(30))->required(),
                ]),

            Section::make('Line Items')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('description')->required()->columnSpan(3),
                            Forms\Components\TextInput::make('quantity')->numeric()->default(1)->columnSpan(1),
                            Forms\Components\TextInput::make('unit_price')->numeric()->prefix('£')->columnSpan(2),
                            Forms\Components\TextInput::make('amount')->numeric()->prefix('£')->disabled()->columnSpan(2),
                        ])
                        ->columns(8)
                        ->defaultItems(1),
                ]),

            Section::make('Totals')
                ->columns(4)
                ->schema([
                    Forms\Components\TextInput::make('subtotal')->numeric()->prefix('£')->disabled(),
                    Forms\Components\TextInput::make('discount_amount')->numeric()->prefix('£')->default(0),
                    Forms\Components\TextInput::make('vat_amount')->numeric()->prefix('£')->disabled(),
                    Forms\Components\TextInput::make('total')->numeric()->prefix('£')->disabled(),
                ]),

            Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
            Forms\Components\Textarea::make('terms')->rows(3)->columnSpanFull()->default('Payment due within 30 days of invoice date.'),
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
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'sent' => 'info', 'partially_paid' => 'primary', 'paid' => 'success', 'overdue' => 'danger', 'cancelled' => 'warning', default => 'gray' }),
                Tables\Columns\TextColumn::make('total')->money('GBP')->sortable(),
                Tables\Columns\TextColumn::make('amount_due')->label('Due')->money('GBP')->sortable(),
                Tables\Columns\TextColumn::make('due_date')->date()->sortable(),
                Tables\Columns\IconColumn::make('sent_at')->label('Sent')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'sent' => 'Sent', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid', 'overdue' => 'Overdue']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $record) => route('invoice.pdf', $record))
                    ->openUrlInNewTab(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view'   => Pages\ViewInvoice::route('/{record}'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}

