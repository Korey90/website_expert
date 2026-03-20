<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SiteSection;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends Controller
{
    public function show(string $slug): Response
    {
        $supported = array_keys(config('languages'));
        $locale    = session('locale');

        if (! $locale || ! in_array($locale, $supported)) {
            $locale = in_array(request()->getPreferredLanguage($supported), $supported)
                ? request()->getPreferredLanguage($supported)
                : $supported[0];
        }

        App::setLocale($locale);

        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $sections = SiteSection::whereIn('key', ['navbar', 'footer'])
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        $navbar = ($s = $sections->get('navbar')) ? ['extra' => $s->extra] : null;
        $footer = ($s = $sections->get('footer')) ? ['extra' => $s->extra] : null;

        return Inertia::render('CmsPage', [
            'page'   => [
                'title'            => $page->getTranslation('title', $locale, true),
                'content'          => $page->getTranslation('content', $locale, true),
                'meta_title'       => $page->getTranslation('meta_title', $locale, true),
                'meta_description' => $page->getTranslation('meta_description', $locale, true),
                'slug'             => $page->slug,
                'type'             => $page->type,
            ],
            'navbar' => $navbar,
            'footer' => $footer,
        ]);
    }
}
