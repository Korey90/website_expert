<?php

namespace App\Http\Controllers;

use App\Models\CalculatorPricing;
use App\Models\CalculatorStep;
use App\Models\CalculatorString;
use App\Models\SiteSection;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
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

        $sections = SiteSection::whereIn('key', ['hero', 'about', 'cta_banner', 'trust_strip', 'testimonials', 'services', 'process', 'portfolio', 'faq', 'cost_calculator', 'navbar', 'contact', 'footer'])
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        $hero = ($s = $sections->get('hero')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'image_path'  => $s->image_path,
            'extra'       => $s->extra,
        ] : null;

        $about = ($s = $sections->get('about')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'body'        => $s->body,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'extra'       => $s->extra,
        ] : null;

        $cta_banner = ($s = $sections->get('cta_banner')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'extra'       => $s->extra,
        ] : null;

        $services = ($s = $sections->get('services')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'extra'       => $s->extra,
        ] : null;

        $portfolio = ($s = $sections->get('portfolio')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'extra'       => $s->extra,
        ] : null;

        $process = ($s = $sections->get('process')) ? [
            'title'    => $s->title,
            'subtitle' => $s->subtitle,
            'extra'    => $s->extra,
        ] : null;

        $faq = ($s = $sections->get('faq')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'extra'       => $s->extra,
        ] : null;

        $trust_strip = ($s = $sections->get('trust_strip')) ? [
            'title' => $s->title,
            'extra' => $s->extra,
        ] : null;

        $testimonials = ($s = $sections->get('testimonials')) ? [
            'title' => $s->title,
            'extra' => $s->extra,
        ] : null;

        $cost_calculator_v2 = $sections->has('cost_calculator') ? true : null;

        $navbar = ($s = $sections->get('navbar')) ? [
            'extra' => $s->extra,
        ] : null;

        $contact = ($s = $sections->get('contact')) ? [
            'title'    => $s->title,
            'subtitle' => $s->subtitle,
            'extra'    => $s->extra,
        ] : null;

        $footer = ($s = $sections->get('footer')) ? [
            'extra' => $s->extra,
        ] : null;

        // Pricing, strings and steps for CostCalculatorV2
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

        $strings = CalculatorString::orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn($s) => [
                $s->key => ($s->{"value_$locale"} ?: $s->value_en) ?? $s->value_en,
            ])
            ->all();

        $steps = CalculatorStep::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($s) => [
                'question' => ($s->{"question_$locale"} ?: $s->question_en) ?? $s->question_en,
                'hint'     => ($s->{"hint_$locale"}     ?: $s->hint_en)     ?? '',
            ])
            ->values()
            ->all();

        return Inertia::render('Welcome', compact('hero', 'about', 'cta_banner', 'trust_strip', 'testimonials', 'services', 'process', 'portfolio', 'faq', 'cost_calculator_v2', 'navbar', 'contact', 'footer', 'pricing', 'strings', 'steps'));
    }
}
