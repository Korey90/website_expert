<?php

namespace App\Filament\Resources\VatAdjustmentResource\Pages;

use App\Actions\Finance\ApproveVatAdjustmentAction;
use App\Actions\Finance\PostVatAdjustmentAction;
use App\Filament\Resources\VatAdjustmentResource;
use App\Models\FinancialAttachment;
use App\Models\VatAdjustment;
use App\Services\Finance\FinancialAttachmentService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class ViewVatAdjustment extends ViewRecord
{
    protected static string $resource = VatAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        /** @var VatAdjustment $adjustment */
        $adjustment = $this->record;

        return [
            Action::make('upload_evidence')
                ->label(__('finance.vat_adjustments.actions.upload_evidence'))
                ->icon('heroicon-o-paper-clip')
                ->visible(fn (): bool => $adjustment->status === VatAdjustment::STATUS_DRAFT && Gate::allows('create', FinancialAttachment::class))
                ->form([
                    FileUpload::make('file')
                        ->label(__('finance.attachments.fields.file'))
                        ->acceptedFileTypes(config('finance.attachments.allowed_mime_types', []))
                        ->maxSize((int) ceil((int) config('finance.attachments.max_bytes', 10485760) / 1024))
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data) use ($adjustment): void {
                    if (! ($data['file'] ?? null) instanceof UploadedFile) {
                        throw new DomainException('A valid attachment file is required.');
                    }

                    $this->runAction(
                        fn () => app(FinancialAttachmentService::class)->store(
                            $adjustment,
                            $data['file'],
                            auth()->user(),
                            'vat_adjustment_evidence',
                        ),
                        __('finance.vat_adjustments.notifications.evidence_uploaded'),
                    );
                }),
            Action::make('approve')
                ->label(__('finance.vat_adjustments.actions.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->visible(fn (): bool => Gate::allows('approve', $adjustment))
                ->requiresConfirmation()
                ->action(fn () => $this->runAction(
                    fn () => app(ApproveVatAdjustmentAction::class)->execute($adjustment, auth()->user()),
                    __('finance.vat_adjustments.notifications.approved'),
                )),
            Action::make('post')
                ->label(__('finance.vat_adjustments.actions.post'))
                ->icon('heroicon-o-lock-closed')
                ->color('success')
                ->visible(fn (): bool => Gate::allows('post', $adjustment))
                ->requiresConfirmation()
                ->action(fn () => $this->runAction(
                    fn () => app(PostVatAdjustmentAction::class)->execute($adjustment, auth()->user()),
                    __('finance.vat_adjustments.notifications.posted'),
                )),
            EditAction::make()->visible(fn (): bool => Gate::allows('update', $adjustment)),
        ];
    }

    private function runAction(callable $action, string $successTitle): void
    {
        try {
            $action();
            Notification::make()->title($successTitle)->success()->send();
            $this->redirect(VatAdjustmentResource::getUrl('view', ['record' => $this->record->id]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
