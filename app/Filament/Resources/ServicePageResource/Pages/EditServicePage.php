<?php

namespace App\Filament\Resources\ServicePageResource\Pages;

use App\Exceptions\LandingPageGenerationException;
use App\Filament\Resources\ServicePageResource;
use App\Models\ServicePage;
use App\Services\ServicePage\ServicePageTranslationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Collection;

class EditServicePage extends EditRecord
{
    protected static string $resource = ServicePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('translateWithAI')
                ->label('Translate with AI')
                ->icon('heroicon-o-language')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Translate page metadata from Polish with AI')
                ->modalDescription('AI wygeneruje angielskie i portugalskie tłumaczenia pól Title, Meta Title, Meta Description i Nav Label na podstawie treści po polsku. Istniejące tłumaczenia EN/PT zostaną nadpisane.')
                ->modalSubmitActionLabel('Generate translations')
                ->action(function (): void {
                    /** @var ServicePage $record */
                    $record = $this->getRecord();

                    $source = [
                        'title'            => $record->getTranslation('title', 'pl'),
                        'meta_title'       => $record->getTranslation('meta_title', 'pl'),
                        'meta_description' => $record->getTranslation('meta_description', 'pl'),
                        'nav_label'        => $record->getTranslation('nav_label', 'pl'),
                    ];

                    if (empty(array_filter($source))) {
                        Notification::make()
                            ->title('Brak treści po polsku')
                            ->body('Uzupełnij przynajmniej pole Title w zakładce Polski i zapisz przed tłumaczeniem.')
                            ->warning()
                            ->send();

                        return;
                    }

                    try {
                        $translations = app(ServicePageTranslationService::class)->translatePage($source);
                    } catch (LandingPageGenerationException $e) {
                        Notification::make()
                            ->title('Translation failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    foreach (['en', 'pt'] as $locale) {
                        $record->setTranslation('title',            $locale, $translations[$locale]['title']);
                        $record->setTranslation('meta_title',       $locale, $translations[$locale]['meta_title']);
                        $record->setTranslation('meta_description', $locale, $translations[$locale]['meta_description']);
                        $record->setTranslation('nav_label',        $locale, $translations[$locale]['nav_label']);
                    }
                    $record->save();

                    Notification::make()
                        ->title('Translations generated')
                        ->body('English and Portuguese metadata fields have been updated.')
                        ->success()
                        ->send();

                    $this->fillForm();
                })
                ->visible(fn (): bool => filled(config('services.openai.api_key'))),

            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ServicePage $record */
        $record = $this->getRecord();

        $data['blocks'] = $record->blocks()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($block) => [
                'block_id'   => $block->id,
                'type'       => $block->type,
                'sort_order' => $block->sort_order,
                'is_active'  => $block->is_active,
                'content'    => $block->content ?? [],
                'settings'   => $block->settings ?? [],
            ])
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncBlocks($this->getRecord());
    }

    /**
     * Normalize Filament-specific storage formats:
     * - FileUpload stores images as {uuid: 'path'} → flatten to string
     * - Repeater stores items as {uuid: {data}} → convert to indexed array
     */
    private static function normalizeContent(array $content): array
    {
        foreach ($content as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            // Empty list — keep as is
            if (array_is_list($value) && count($value) === 0) {
                continue;
            }

            // Associative array with non-integer keys = UUID-keyed Repeater items or FileUpload
            if (!array_is_list($value)) {
                $firstVal = array_values($value)[0] ?? null;

                // FileUpload: {uuid: 'some/path.jpg'} → string
                if (is_string($firstVal) && str_contains((string) $firstVal, '/')) {
                    $content[$key] = $firstVal;
                    continue;
                }

                // Repeater sub-items: {uuid: [...]} → indexed array, each item also normalized
                if (is_array($firstVal)) {
                    $content[$key] = array_values(array_map(
                        fn(array $item) => self::normalizeContent($item),
                        array_filter($value, 'is_array')
                    ));
                    continue;
                }
            }

            // List of arrays — normalize each item recursively
            if (array_is_list($value)) {
                $content[$key] = array_map(
                    fn($item) => is_array($item) ? self::normalizeContent($item) : $item,
                    $value
                );
            }
        }
        return $content;
    }

    private function syncBlocks(ServicePage $page): void
    {
        $rawBlocks = collect($this->data['blocks'] ?? []);

        // Keep only blocks still present in the form
        $keepIds = $rawBlocks->pluck('block_id')->filter()->values()->toArray();
        $page->blocks()->whereNotIn('id', $keepIds)->delete();

        foreach ($rawBlocks->values() as $index => $blockData) {
            $attrs = [
                'service_page_id' => $page->id,
                'type'            => $blockData['type'] ?? 'hero',
                'sort_order'      => $index + 1,
                'is_active'       => (bool) ($blockData['is_active'] ?? true),
                'content'         => self::normalizeContent(is_array($blockData['content'] ?? null) ? $blockData['content'] : []),
                'settings'        => is_array($blockData['settings'] ?? null) ? $blockData['settings'] : [],
            ];

            $blockId = $blockData['block_id'] ?? null;

            if ($blockId) {
                $page->blocks()->whereKey((int) $blockId)->update($attrs);
            } else {
                $page->blocks()->create($attrs);
            }
        }
    }
}

