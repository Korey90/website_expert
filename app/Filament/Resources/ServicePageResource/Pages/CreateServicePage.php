<?php

namespace App\Filament\Resources\ServicePageResource\Pages;

use App\Filament\Resources\ServicePageResource;
use App\Models\ServicePage;
use Filament\Resources\Pages\CreateRecord;

class CreateServicePage extends CreateRecord
{
    protected static string $resource = ServicePageResource::class;

    protected function afterCreate(): void
    {
        /** @var ServicePage $page */
        $page = $this->getRecord();

        foreach (collect($this->data['blocks'] ?? [])->values() as $index => $blockData) {
            $page->blocks()->create([
                'service_page_id' => $page->id,
                'type'            => $blockData['type'] ?? 'hero',
                'sort_order'      => $index + 1,
                'is_active'       => (bool) ($blockData['is_active'] ?? true),
                'content'         => self::normalizeContent(is_array($blockData['content'] ?? null) ? $blockData['content'] : []),
                'settings'        => is_array($blockData['settings'] ?? null) ? $blockData['settings'] : [],
            ]);
        }
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

            if (array_is_list($value) && count($value) === 0) {
                continue;
            }

            if (!array_is_list($value)) {
                $firstVal = array_values($value)[0] ?? null;

                if (is_string($firstVal) && str_contains((string) $firstVal, '/')) {
                    $content[$key] = $firstVal;
                    continue;
                }

                if (is_array($firstVal)) {
                    $content[$key] = array_values(array_map(
                        fn(array $item) => self::normalizeContent($item),
                        array_filter($value, 'is_array')
                    ));
                    continue;
                }
            }

            if (array_is_list($value)) {
                $content[$key] = array_map(
                    fn($item) => is_array($item) ? self::normalizeContent($item) : $item,
                    $value
                );
            }
        }
        return $content;
    }
}
