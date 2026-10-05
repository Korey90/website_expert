<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\SiteSection;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    /**
     * Context-aware CTA shown at the end of each article, keyed by category.
     */
    private const CATEGORY_CTA = [
        'pricing' => ['url' => '/calculate', 'label_en' => 'Estimate your project cost', 'label_pl' => 'Oszacuj koszt swojego projektu'],
        'seo' => ['url' => '/services/seo', 'label_en' => 'See our SEO service', 'label_pl' => 'Zobacz naszą usługę SEO'],
        'comparison' => ['url' => '/services/brochure-websites', 'label_en' => 'See our brochure website service', 'label_pl' => 'Zobacz nasze strony wizytówkowe'],
        'pl-business' => ['url' => '/pl/strony-internetowe-uk', 'label_en' => 'Strony internetowe dla polskich firm w UK', 'label_pl' => 'Strony internetowe dla polskich firm w UK'],
    ];

    private function resolveLocale(): string
    {
        $supported = array_keys(config('languages'));
        $locale = session('locale');

        if (! $locale || ! in_array($locale, $supported)) {
            $locale = in_array(request()->getPreferredLanguage($supported), $supported)
                ? request()->getPreferredLanguage($supported)
                : $supported[0];
        }

        App::setLocale($locale);

        return $locale;
    }

    private function sharedSections(): array
    {
        $footer = SiteSection::where('key', 'footer')->where('is_active', true)->first();

        return [
            'footer' => $footer ? ['extra' => $footer->extra] : null,
        ];
    }

    private function mapArticle(Article $article, string $locale): array
    {
        return [
            'title' => $article->getTranslation('title', $locale, true),
            'slug' => $article->slug,
            'excerpt' => $article->getTranslation('excerpt', $locale, true),
            'category' => $article->category,
            'featured_image_path' => $article->featured_image_path,
            'published_at' => $article->published_at?->format('j F Y'),
        ];
    }

    public function index(): Response
    {
        $locale = $this->resolveLocale();

        $articles = Article::published()
            ->category(request()->string('category')->toString() ?: null)
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        $articles->getCollection()->transform(fn (Article $article) => $this->mapArticle($article, $locale));

        return Inertia::render('Blog/Index', array_merge($this->sharedSections(), [
            'locale' => $locale,
            'articles' => $articles,
            'category' => request()->string('category')->toString() ?: null,
        ]));
    }

    public function show(string $slug): Response
    {
        $locale = $this->resolveLocale();

        $article = Article::published()->where('slug', $slug)->firstOrFail();

        $related = Article::published()
            ->where('id', '!=', $article->id)
            ->category($article->category)
            ->latest('published_at')
            ->take(3)
            ->get()
            ->map(fn (Article $item) => $this->mapArticle($item, $locale))
            ->values();

        $cta = self::CATEGORY_CTA[$article->category] ?? self::CATEGORY_CTA['pl-business'];

        return Inertia::render('Blog/Show', array_merge($this->sharedSections(), [
            'locale' => $locale,
            'article' => [
                'title' => $article->getTranslation('title', $locale, true),
                'slug' => $article->slug,
                'excerpt' => $article->getTranslation('excerpt', $locale, true),
                'content' => $article->getTranslation('content', $locale, true),
                'meta_title' => $article->getTranslation('meta_title', $locale, true),
                'meta_description' => $article->getTranslation('meta_description', $locale, true),
                'category' => $article->category,
                'featured_image_path' => $article->featured_image_path,
                'published_at' => $article->published_at?->format('j F Y'),
            ],
            'related' => $related,
            'cta' => [
                'url' => $cta['url'],
                'label' => $locale === 'pl' ? $cta['label_pl'] : $cta['label_en'],
            ],
        ]));
    }
}
