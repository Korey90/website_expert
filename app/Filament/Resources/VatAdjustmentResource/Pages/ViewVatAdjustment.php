<?php

namespace App\Filament\Resources\VatAdjustmentResource\Pages;

use App\Actions\Finance\ApproveVatAdjustmentAction;
use App\Actions\Finance\PostVatAdjustmentAction;
use App\Actions\Finance\RetryFinancialAttachmentScanAction;
use App\Filament\Resources\VatAdjustmentResource;
use App\Models\FinancialAttachment;
use App\Models\VatAdjustment;
use App\Services\Finance\FinancialAttachmentService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
            Action::make('retry_attachment_scan')
                ->label(__('finance.attachments.actions.retry_scan'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => Gate::allows('create', FinancialAttachment::class)
                    && $adjustment->attachments()
                        ->whereIn('scan_status', [
                            FinancialAttachment::SCAN_PENDING,
                            FinancialAttachment::SCAN_FAILED,
                        ])
                        ->exists())
                ->form([
                    Select::make('attachment_id')
                        ->label(__('finance.attachments.fields.file'))
                        ->options(fn (): array => $adjustment->attachments()
                            ->whereIn('scan_status', [
                                FinancialAttachment::SCAN_PENDING,
                                FinancialAttachment::SCAN_FAILED,
                            ])
                            ->orderBy('original_name')
                            ->pluck('original_name', 'id')
                            ->all())
                        ->required(),
                ])
                ->action(function (array $data) use ($adjustment): void {
                    $attachment = $adjustment->attachments()
                        ->whereIn('scan_status', [
                            FinancialAttachment::SCAN_PENDING,
                            FinancialAttachment::SCAN_FAILED,
                        ])
                        ->findOrFail((int) $data['attachment_id']);

                    $this->runAction(
                        fn () => app(RetryFinancialAttachmentScanAction::class)->execute(
                            $attachment,
                            auth()->user(),
                        ),
                        __('finance.attachments.notifications.scan_queued'),
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
