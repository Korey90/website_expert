<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailTemplate extends ViewRecord
{
    protected static string $resource = EmailTemplateResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.email_templates.view_subheading');
    }

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
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
