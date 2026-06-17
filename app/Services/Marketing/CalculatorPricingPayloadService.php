<?php

namespace App\Services\Marketing;

use App\Models\CalculatorPricing;
use App\Services\Currency\CurrencyResolver;
use Illuminate\Support\Collection;

class CalculatorPricingPayloadService
{
    /**
     * @var array<string, string>
     */
    private array $categoryMap = [
        'project_type' => 'projectType',
        'pages_addon' => 'pagesAddon',
        'design' => 'design',
        'cms' => 'cms',
        'integrations' => 'integrations',
        'seo_package' => 'seoPackage',
        'deadline' => 'deadline',
        'hosting' => 'hosting',
    ];

    /**
     * @var array<string, int>
     */
    private array $categoryOrder = [
        'project_type' => 1,
        'pages_addon' => 2,
        'design' => 3,
        'cms' => 4,
        'integrations' => 5,
        'seo_package' => 6,
        'deadline' => 7,
        'hosting' => 8,
    ];

    /**
     * @var array<int, string>
     */
    private array $multiplierCategories = ['design', 'deadline'];

    public function __construct(
        private readonly CurrencyResolver $currencyResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forLocale(string $locale): array
    {
        $requestedCurrency = $this->currencyResolver->resolve(null, $locale);
        $defaultCurrency = $this->currencyResolver->defaultCurrency();
        $currency = $this->resolvePriceBookCurrency($requestedCurrency, $defaultCurrency);

        $rows = CalculatorPricing::where('is_active', true)
            ->where('currency', $currency)
            ->orderBy('sort_order')
            ->get();

        $pricing = [
            '_currency' => $currency,
        ];

        $this->sortRowsForPayload($rows)
            ->each(function (CalculatorPricing $row) use (&$pricing, $locale): void {
                $frontendKey = $this->categoryMap[$row->category] ?? null;
                if (! $frontendKey) {
                    return;
                }

                $entry = [
                    'label_en' => $row->label,
                    'label_pl' => $row->label_pl ?? $row->label,
                    'label_pt' => $row->label_pt ?? $row->label,
                    'label_loc' => $row->{"label_{$locale}"} ?? $row->label,
                    'icon' => $row->icon ?? '',
                    'desc_en' => $row->description ?? '',
                    'desc_pl' => $row->desc_pl ?? $row->description ?? '',
                    'desc_pt' => $row->desc_pt ?? $row->description ?? '',
                    'desc_loc' => $row->{"desc_{$locale}"} ?? $row->description ?? '',
                    'currency' => $row->currency,
                    'monthly' => (float) $row->monthly_cost,
                ];

                if ($row->category === 'project_type') {
                    $entry['base'] = (float) $row->base_cost;
                } elseif (in_array($row->category, $this->multiplierCategories, true)) {
                    $entry['multiplier'] = (float) $row->multiplier;
                } else {
                    $entry['cost'] = (float) $row->base_cost;
                }

                $pricing[$frontendKey][$row->key] = $entry;
            });

        return $pricing;
    }

    /**
     * @param  Collection<int, CalculatorPricing>  $rows
     * @return Collection<int, CalculatorPricing>
     */
    private function sortRowsForPayload(Collection $rows): Collection
    {
        return $rows
            ->sortBy(fn (CalculatorPricing $row): string => sprintf(
                '%02d-%05d-%s',
                $this->categoryOrder[$row->category] ?? 99,
                (int) $row->sort_order,
                $row->key,
            ))
            ->values();
    }

    private function resolvePriceBookCurrency(string $requestedCurrency, string $defaultCurrency): string
    {
        if ($requestedCurrency === $defaultCurrency) {
            return $defaultCurrency;
        }

        $defaultKeys = $this->activeKeysForCurrency($defaultCurrency);
        if ($defaultKeys->isEmpty()) {
            return $requestedCurrency;
        }

        $requestedKeys = $this->activeKeysForCurrency($requestedCurrency);

        return $defaultKeys->diff($requestedKeys)->isEmpty()
            ? $requestedCurrency
            : $defaultCurrency;
    }

    /**
     * @return Collection<int, string>
     */
    private function activeKeysForCurrency(string $currency): Collection
    {
        return CalculatorPricing::where('is_active', true)
            ->where('currency', $currency)
            ->get(['category', 'key'])
            ->map(fn (CalculatorPricing $row): string => "{$row->category}|{$row->key}")
            ->unique()
            ->values();
    }
}
