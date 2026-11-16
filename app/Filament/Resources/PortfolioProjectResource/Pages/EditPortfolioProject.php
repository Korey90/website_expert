<?php

namespace App\Filament\Resources\PortfolioProjectResource\Pages;

use App\Exceptions\LandingPageGenerationException;
use App\Filament\Resources\PortfolioProjectResource;
use App\Models\PortfolioProject;
use App\Services\Portfolio\PortfolioTranslationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPortfolioProject extends EditRecord
{
    protected static string $resource = PortfolioProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('translateWithAI')
                ->label('Translate with AI')
                ->icon('heroicon-o-language')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Translate from English with AI')
                ->modalDescription('AI wygeneruje angielskie i portugalskie tłumaczenia na podstawie treści po polsku zapisanej dla tego rekordu. Istniejące tłumaczenia EN/PT zostaną nadpisane.')
                ->modalSubmitActionLabel('Generate translations')
                ->action(function (): void {
                    /** @var PortfolioProject $record */
                    $record = $this->getRecord();

                    $source = [
                        'title'       => $record->getTranslation('title', 'pl'),
                        'tag'         => $record->getTranslation('tag', 'pl'),
                        'description' => $record->getTranslation('description', 'pl'),
                        'result'      => $record->getTranslation('result', 'pl'),
                    ];

                    if (empty(array_filter($source))) {
                        Notification::make()
                            ->title('Brak treści po polsku')
                            ->body('Uzupełnij pola w zakładce Polski i zapisz przed tłumaczeniem.')
                            ->warning()
                            ->send();

                        return;
                    }

                    try {
                        $translations = app(PortfolioTranslationService::class)->translate($source);
                    } catch (LandingPageGenerationException $e) {
                        Notification::make()
                            ->title('Translation failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    foreach (['en', 'pt'] as $locale) {
                        $record->setTranslation('title',       $locale, $translations[$locale]['title']);
                        $record->setTranslation('tag',         $locale, $translations[$locale]['tag']);
                        $record->setTranslation('description', $locale, $translations[$locale]['description']);
                        $record->setTranslation('result',      $locale, $translations[$locale]['result']);
                    }
                    $record->save();

                    Notification::make()
                        ->title('Translations generated')
                        ->body('Polish and Portuguese fields have been updated. Review the tabs and save.')
                        ->success()
                        ->send();

                    $this->fillForm();
                })
                ->visible(fn (): bool => filled(config('services.openai.api_key'))),

            DeleteAction::make(),
        ];
    }
}

