<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected string $view = 'filament.resources.quote-resource.pages.view-quote';

    protected function getHeaderActions(): array
    {
        $quote = $this->record;
        return [
            Action::make('send_quote')
                ->label('Send Quote')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => $quote->status === 'draft')
                ->requiresConfirmation()
                ->modalHeading('Send Quote to Client')
                ->modalDescription('This will mark the quote as Sent and make it visible to the client in their portal.')
                ->modalSubmitActionLabel('Yes, Send Quote')
                ->action(function () use ($quote) {
                    $quote->update([
                        'status'  => 'sent',
                        'sent_at' => now(),
                    ]);
                    \Filament\Notifications\Notification::make()
                        ->title('Quote sent!')
                        ->success()
                        ->send();
                }),
            Action::make('create_contract')
                ->label('Create Contract')
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->visible(fn () => in_array($quote->status, ['accepted', 'sent']) && !Contract::where('quote_id', $quote->id)->exists())
                ->url(fn () => route('filament.admin.resources.contracts.create', [
                    'client_id'  => $quote->client_id,
                    'quote_id'   => $quote->id,
                    'value'      => $quote->total,
                    'currency'   => $quote->currency,
                ])),
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
