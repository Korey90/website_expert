<?php

namespace App\Http\Controllers;

use App\Models\ServicePage;
use App\Models\SiteSection;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

class ServicePageController extends Controller
{
    public function show(string $slug): Response|\Illuminate\Http\RedirectResponse
    {
        $page = ServicePage::where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (! $page) {
            abort(404);
        }

        $supported = array_keys(config('languages'));
        $locale    = session('locale');

        if (! $locale || ! in_array($locale, $supported)) {
            $locale = in_array(request()->getPreferredLanguage($supported), $supported)
                ? request()->getPreferredLanguage($supported)
                : $supported[0];
        }

        App::setLocale($locale);

        $footer = ($s = SiteSection::where('key', 'footer')->where('is_active', true)->first())
            ? ['extra' => $s->extra]
            : null;

        $blocks = $page->activeBlocks->map(fn ($b) => [
            'id'       => $b->id,
            'type'     => $b->type,
            'content'  => $b->content ?? [],
            'settings' => $b->settings ?? [],
        ])->values()->all();

        return Inertia::render('Services/ServicePage', [
            'page'   => [
                'slug'             => $page->slug,
                'title'            => $page->getTranslation('title', $locale, true),
                'meta_title'       => $page->getTranslation('meta_title', $locale, true),
                'meta_description' => $page->getTranslation('meta_description', $locale, true),
            ],
            'blocks' => $blocks,
            'locale' => $locale,
            'auth'   => auth()->check() ? ['user' => auth()->user()->only('id', 'name')] : null,
            'footer' => $footer,
        ]);
    }
}
