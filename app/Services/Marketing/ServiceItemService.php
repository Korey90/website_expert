<?php

namespace App\Services\Marketing;

use App\Models\ServiceItem;
use Illuminate\Support\Collection;

class ServiceItemService
{
    public function getFeatured(int $limit = 9): Collection
    {
        return ServiceItem::featured()->active()->ordered()->limit($limit)->get();
    }

    public function getAll(): Collection
    {
        return ServiceItem::active()->ordered()->get();
    }

    public function create(array $data): ServiceItem
    {
        return ServiceItem::create($data);
    }

    public function update(ServiceItem $item, array $data): ServiceItem
    {
        $item->update($data);

        return $item->fresh();
    }

    public function delete(ServiceItem $item): void
    {
        $item->delete();
    }

    public function findBySlug(string $slug): ?ServiceItem
    {
        return ServiceItem::where('slug', $slug)->first();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            ServiceItem::where('id', $id)->update(['sort_order' => $position + 1]);
        }
    }

    public function mapToArray(Collection $items, string $locale = 'en'): array
    {
        return $items->map(fn (ServiceItem $item) => [
            'icon'                  => $item->icon,
            'title_en'              => $item->getTranslation('title', 'en'),
            'title_pl'              => $item->getTranslation('title', 'pl'),
            'title_pt'              => $item->getTranslation('title', 'pt'),
            'description_en'        => $item->getTranslation('description', 'en') ?? '',
            'description_pl'        => $item->getTranslation('description', 'pl') ?? '',
            'description_pt'        => $item->getTranslation('description', 'pt') ?? '',
            'price_from'            => $item->price_from,
            'link'                  => $item->link,
            'slug'                  => $item->slug,
            'is_active'             => $item->is_active,
            'is_featured'           => $item->is_featured,
            'badge_text_en'         => $item->getTranslation('badge_text', 'en') ?? '',
            'badge_text_pl'         => $item->getTranslation('badge_text', 'pl') ?? '',
            'badge_text_pt'         => $item->getTranslation('badge_text', 'pt') ?? '',
            'image_path'            => $item->image_path,
        ])->values()->all();
    }
}
