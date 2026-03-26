<?php

namespace App\Http\Controllers;

use App\Models\CalculatorPricing;
use App\Models\CalculatorStep;
use App\Models\CalculatorString;
use App\Models\SiteSection;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

class KalkulatorController extends Controller
{
    public function __invoke(): Response
    {
        $supported = array_keys(config('languages'));
        $locale    = session('locale');

        if (! $locale || ! in_array($locale, $supported)) {
            $locale = in_array(request()->getPreferredLanguage($supported), $supported)
                ? request()->getPreferredLanguage($supported)
                : $supported[0];
        }

        App::setLocale($locale);

        $sections = SiteSection::whereIn('key', ['navbar', 'footer'])
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        $navbar = ($s = $sections->get('navbar')) ? [
            'extra' => $s->extra,
        ] : null;

        $footer = ($s = $sections->get('footer')) ? [
            'extra' => $s->extra,
        ] : null;

        // Build pricing object from DB, shaped to match the frontend PRICING structure
        $categoryMap = [
            'project_type' => 'projectType',
            'design'       => 'design',
            'cms'          => 'cms',
            'integrations' => 'integrations',
            'seo_package'  => 'seoPackage',
            'deadline'     => 'deadline',
            'hosting'      => 'hosting',
        ];
        $multiplierCategories = ['design', 'deadline'];

        $pricing = [];
        CalculatorPricing::where('is_active', true)
            ->orderBy('sort_order')
            ->each(function ($row) use (&$pricing, $categoryMap, $multiplierCategories, $locale) {
                $frontendKey = $categoryMap[$row->category] ?? null;
                if (!$frontendKey) return;

                $entry = [
                    'label_en'  => $row->label,
                    'label_pl'  => $row->label_pl ?? $row->label,
                    'label_pt'  => $row->label_pt ?? $row->label,
                    // Pre-resolved for CostCalculatorV2 (locale already known server-side)
                    'label_loc' => $row->{"label_$locale"} ?? $row->label,
                    'icon'      => $row->icon ?? '',
                    'desc_en'   => $row->description ?? '',
                    'desc_pl'   => $row->desc_pl ?? $row->description ?? '',
                    'desc_pt'   => $row->desc_pt ?? $row->description ?? '',
                    'desc_loc'  => $row->{"desc_$locale"} ?? $row->description ?? '',
                ];

                if ($row->category === 'project_type') {
                    $entry['base'] = (float) $row->base_cost;
                } elseif (in_array($row->category, $multiplierCategories)) {
                    $entry['multiplier'] = (float) $row->multiplier;
                } else {
                    $entry['cost'] = (float) $row->base_cost;
                }

                $pricing[$frontendKey][$row->key] = $entry;
            });

        // Build calculator UI strings — pre-resolved to the active locale
        $strings = CalculatorString::orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn($s) => [
                $s->key => ($s->{"value_$locale"} ?: $s->value_en) ?? $s->value_en,
            ])
            ->all();

        // Build calculator steps — pre-resolved to the active locale
        $steps = CalculatorStep::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($s) => [
                'question' => ($s->{"question_$locale"} ?: $s->question_en) ?? $s->question_en,
                'hint'     => ($s->{"hint_$locale"}     ?: $s->hint_en)     ?? '',
            ])
            ->values()
            ->all();

        return Inertia::render('Kalkulator', compact('navbar', 'footer', 'pricing', 'strings', 'steps'));
    }
}
