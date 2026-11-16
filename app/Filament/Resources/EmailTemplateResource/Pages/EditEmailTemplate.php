<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use App\Models\EmailTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewEmail')
                ->label('Preview Email')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading('Email Preview')
                ->modalWidth('4xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn () => view('filament.email-template-preview', [
                    'record' => $this->getRecord(),
                ])),
            DeleteAction::make(),
        ];
    }
}
