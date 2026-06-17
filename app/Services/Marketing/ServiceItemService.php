<?php

namespace App\Services\Marketing;

use App\Models\ServiceItem;
use App\Services\Currency\CurrencyResolver;
use App\Services\Currency\MoneyFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ServiceItemService
{
    public function __construct(
        private readonly CurrencyResolver $currencyResolver,
        private readonly MoneyFormatter $moneyFormatter,
    ) {}

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
        return $items->map(fn (ServiceItem $item) => $this->toPublicArray($item, $locale))->values()->all();
    }

    public function toPublicArray(ServiceItem $item, string $locale = 'en', bool $withDetail = false): array
    {
        $data = [
            'icon' => $item->icon,
            'title_en' => $item->getTranslation('title', 'en'),
            'title_pl' => $item->getTranslation('title', 'pl'),
            'title_pt' => $item->getTranslation('title', 'pt'),
            'description_en' => $item->getTranslation('description', 'en') ?? '',
            'description_pl' => $item->getTranslation('description', 'pl') ?? '',
            'description_pt' => $item->getTranslation('description', 'pt') ?? '',
            'link' => $item->link,
            'slug' => $item->slug,
            'is_active' => (bool) $item->is_active,
            'is_featured' => (bool) $item->is_featured,
            'badge_text_en' => $item->getTranslation('badge_text', 'en') ?? '',
            'badge_text_pl' => $item->getTranslation('badge_text', 'pl') ?? '',
            'badge_text_pt' => $item->getTranslation('badge_text', 'pt') ?? '',
            'image_path' => $item->image_path,
            'image_url' => $item->image_path ? Storage::disk('public')->url($item->image_path) : null,
        ];

        $data = array_merge($data, $this->priceFromFields($item, $locale));

        if (! $withDetail) {
            return $data;
        }

        return array_merge($data, [
            'cta_url' => $item->cta_url,
            'body_en' => $item->getTranslation('body', 'en') ?? '',
            'body_pl' => $item->getTranslation('body', 'pl') ?? '',
            'body_pt' => $item->getTranslation('body', 'pt') ?? '',
            'cta_label_en' => $item->getTranslation('cta_label', 'en') ?? '',
            'cta_label_pl' => $item->getTranslation('cta_label', 'pl') ?? '',
            'cta_label_pt' => $item->getTranslation('cta_label', 'pt') ?? '',
            'meta_title_en' => $item->getTranslation('meta_title', 'en') ?? '',
            'meta_title_pl' => $item->getTranslation('meta_title', 'pl') ?? '',
            'meta_title_pt' => $item->getTranslation('meta_title', 'pt') ?? '',
            'meta_description_en' => $item->getTranslation('meta_description', 'en') ?? '',
            'meta_description_pl' => $item->getTranslation('meta_description', 'pl') ?? '',
            'meta_description_pt' => $item->getTranslation('meta_description', 'pt') ?? '',
            'features' => $item->features ?? [],
            'faq' => $item->faq ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function priceFromFields(ServiceItem $item, string $locale): array
    {
        $resolvedCurrency = $this->currencyResolver->resolve(null, $locale);
        $priceBook = is_array($item->price_from_prices) ? $item->price_from_prices : [];
        $amount = $this->amountForCurrency($priceBook, $resolvedCurrency);
        $currency = $resolvedCurrency;

        if ($amount === null) {
            $currency = $this->currencyResolver->defaultCurrency();
            $amount = $this->amountForCurrency($priceBook, $currency);
        }

        return [
            'price_from' => $item->price_from,
            'price_from_amount' => $amount,
            'price_from_currency' => $amount === null ? null : $currency,
            'price_from_period' => $item->price_from_period,
            'price_from_formatted' => $amount === null
                ? $item->price_from
                : $this->moneyFormatter->format($amount, $currency, $locale),
        ];
    }

    /**
     * @param  array<mixed>  $priceBook
     */
    private function amountForCurrency(array $priceBook, string $currency): ?float
    {
        $currency = strtoupper($currency);

        if (array_key_exists($currency, $priceBook)) {
            return $this->numericAmount($priceBook[$currency]);
        }

        foreach ($priceBook as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $entryCurrency = strtoupper((string) ($entry['currency'] ?? ''));
            if ($entryCurrency === $currency) {
                return $this->numericAmount($entry['amount'] ?? $entry['price'] ?? $entry['value'] ?? null);
            }
        }

        return null;
    }

    private function numericAmount(mixed $value): ?float
    {
        if (is_array($value)) {
            $value = $value['amount'] ?? $value['price'] ?? $value['value'] ?? null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }
}
