<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Setting;
use App\Models\SiteSection;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

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

        $sections = SiteSection::whereIn('key', ['footer'])
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        $footer = ($s = $sections->get('footer')) ? ['extra' => $s->extra] : null;

        $content = $this->replaceLegalVars(
            $page->getTranslation('content', $locale, true) ?? ''
        );

        return Inertia::render('CmsPage', [
            'page'   => [
                'title'            => $page->getTranslation('title', $locale, true),
                'content'          => $content,
                'meta_title'       => $page->getTranslation('meta_title', $locale, true),
                'meta_description' => $page->getTranslation('meta_description', $locale, true),
                'slug'             => $page->slug,
                'type'             => $page->type,
                'effective_date'   => $page->effective_date?->format('j F Y'),
                'version'          => $page->version,
                'updated_at'       => $page->updated_at?->format('j F Y'),
            ],
            'footer' => $footer,
        ]);
    }

    private function replaceLegalVars(string $content): string
    {
        $vars = Setting::where('group', 'legal')->pluck('value', 'key');

        foreach ($vars as $key => $value) {
            $content = str_replace('{{' . $key . '}}', htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $content);
        }

        return $content;
    }
}
