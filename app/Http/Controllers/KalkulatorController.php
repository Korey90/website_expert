<?php

namespace App\Http\Controllers;

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

        $sections = SiteSection::whereIn('key', ['cost_calculator', 'navbar', 'footer'])
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        $cost_calculator = ($s = $sections->get('cost_calculator')) ? [
            'title'    => $s->title,
            'subtitle' => $s->subtitle,
            'extra'    => $s->extra,
        ] : null;

        $navbar = ($s = $sections->get('navbar')) ? [
            'extra' => $s->extra,
        ] : null;

        $footer = ($s = $sections->get('footer')) ? [
            'extra' => $s->extra,
        ] : null;

        return Inertia::render('Kalkulator', compact('cost_calculator', 'navbar', 'footer'));
    }
}
