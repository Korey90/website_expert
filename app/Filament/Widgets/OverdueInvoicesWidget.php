<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OverdueInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 7;
    protected int|string|array $columnSpan = 'full';
    protected static bool $isLazy = true;
    protected static ?string $heading = 'Overdue & Unpaid Invoices';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::withoutTrashed()
                    ->whereIn('status', ['overdue', 'sent', 'partially_paid'])
                    ->orderBy('due_date')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Invoice')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'overdue'        => 'danger',
                        'sent'           => 'info',
                        'partially_paid' => 'warning',
                        default          => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total')->money('GBP'),
                Tables\Columns\TextColumn::make('amount_due')->label('Due')->money('GBP'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->color(fn ($record) => $record->due_date < now() ? 'danger' : null),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn (Invoice $record) => route('filament.admin.resources.invoices.edit', $record))
                    ->icon('heroicon-m-pencil-square'),
            ]);
    }
}
