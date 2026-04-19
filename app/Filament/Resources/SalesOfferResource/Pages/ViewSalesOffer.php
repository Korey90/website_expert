<?php

namespace App\Filament\Resources\SalesOfferResource\Pages;

use App\Filament\Resources\SalesOfferResource;
use App\Models\SalesOffer;
use App\Services\SalesOfferService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesOffer extends ViewRecord
{
    protected static string $resource = SalesOfferResource::class;
    protected string $view = 'filament.pages.view-sales-offer';

    public string $body = '';
    public ?string $notes = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var SalesOffer $offer */
        $offer = $this->getRecord();

        $this->body  = $offer->body ?? '';
        $this->notes = $offer->notes;
    }

    public function updatedBody(): void
    {
        $this->autosave();
    }

    public function autosave(): void
    {
        /** @var SalesOffer $offer */
        $offer = $this->getRecord();

        if (! $offer->isEditable()) {
            return;
        }

        $offer->update(['body' => $this->body]);
        $this->record = $offer->fresh();
    }

    public function saveDraft(): void
    {
        /** @var SalesOffer $offer */
        $offer = $this->getRecord();

        if (! $offer->isEditable()) {
            Notification::make()
                ->title('Offer is not editable.')
                ->warning()
                ->send();
            return;
        }

        $offer->update([
            'body'  => $this->body,
            'notes' => $this->notes,
        ]);

        $this->record = $offer->fresh();

        Notification::make()
            ->title('Draft saved.')
            ->success()
            ->send();
    }

    public function sendOffer(): void
    {
        /** @var SalesOffer $offer */
        $offer = $this->getRecord();

        // Save latest body before sending
        $offer->update(['body' => $this->body, 'notes' => $this->notes]);

        try {
            app(SalesOfferService::class)->send($offer);
            $this->record = $offer->fresh();

            Notification::make()
                ->title('Offer sent successfully.')
                ->success()
                ->send();
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Cannot send: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function convertToQuote(): void
    {
        /** @var SalesOffer $offer */
        $offer = $this->getRecord();

        app(SalesOfferService::class)->convertToQuote($offer);
        $this->record = $offer->fresh();

        Notification::make()
            ->title('Offer marked as converted.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        /** @var SalesOffer $offer */
        $offer = $this->getRecord();

        $actions = [];

        if ($offer->isEditable()) {
            $actions[] = Action::make('save_draft')
                ->label('Save draft')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('gray')
                ->action('saveDraft');

            $actions[] = Action::make('send_offer')
                ->label('Send offer')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Send offer to client?')
                ->modalDescription('An email with a personalised link will be sent to the client. This action cannot be undone.')
                ->action('sendOffer');
        }

        if ($offer->isSent()) {
            $actions[] = Action::make('convert_to_quote')
                ->label('Mark as converted')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->action('convertToQuote');
        }

        $actions[] = Action::make('export_pdf')
            ->label('Export PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function () {
                $offer = $this->getRecord()->load(['lead', 'createdBy', 'template', 'business']);
                $pdf = Pdf::loadView('pdf.sales-offer', compact('offer'));
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'sales-offer-' . $offer->id . '.pdf'
                );
            });

        $actions[] = DeleteAction::make();

        return $actions;
    }

    public function getOffer(): SalesOffer
    {
        return $this->getRecord();
    }
}
