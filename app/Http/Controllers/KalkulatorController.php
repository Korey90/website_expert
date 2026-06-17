<?php

namespace App\Http\Controllers;

use App\Models\CalculatorStep;
use App\Models\CalculatorString;
use App\Models\SiteSection;
use App\Services\Marketing\CalculatorPricingPayloadService;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

class KalkulatorController extends Controller
{
    public function __invoke(): Response
    {
        $supported = array_keys(config('languages'));
        $locale = session('locale');

        if (! $locale || ! in_array($locale, $supported)) {
            $locale = in_array(request()->getPreferredLanguage($supported), $supported)
                ? request()->getPreferredLanguage($supported)
                : $supported[0];
        }

        App::setLocale($locale);

        $sections = SiteSection::whereIn('key', ['footer'])
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        $footer = ($s = $sections->get('footer')) ? [
            'extra' => $s->extra,
        ] : null;

        // Build pricing object from DB, shaped to match the frontend PRICING structure
        $pricing = app(CalculatorPricingPayloadService::class)->forLocale($locale);

        // Build calculator UI strings — pre-resolved to the active locale
        $strings = CalculatorString::orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn ($s) => [
                $s->key => ($s->{"value_$locale"} ?: $s->value_en) ?? $s->value_en,
            ])
            ->all();

        // Build calculator steps — pre-resolved to the active locale
        $steps = CalculatorStep::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($s) => [
                'question' => ($s->{"question_$locale"} ?: $s->question_en) ?? $s->question_en,
                'hint' => ($s->{"hint_$locale"} ?: $s->hint_en) ?? '',
            ])
            ->values()
            ->all();

        return Inertia::render('Kalkulator', compact('footer', 'pricing', 'strings', 'steps'));
    }
}
