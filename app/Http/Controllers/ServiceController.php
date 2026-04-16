<?php

namespace App\Http\Controllers;

use App\Models\SiteSection;
use App\Services\Marketing\ServiceItemService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceItemService $service,
    ) {}

    private function resolveLocale(): string
    {
        $supported = array_keys(config('languages'));
        $locale    = session('locale');

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
        $sections = SiteSection::where('is_active', true)
            ->whereIn('key', ['navbar', 'footer'])
            ->get()
            ->keyBy('key');

        return [
            'navbar' => ($s = $sections->get('navbar')) ? ['extra' => $s->extra] : null,
            'footer' => ($s = $sections->get('footer')) ? ['extra' => $s->extra] : null,
        ];
    }

    private function mapItem(mixed $s): array
    {
        return [
            // Core
            'icon'                  => $s->icon,
            'price_from'            => $s->price_from,
            'link'                  => $s->link,
            'slug'                  => $s->slug,
            'is_active'             => (bool) $s->is_active,
            'is_featured'           => (bool) $s->is_featured,
            'image_url'             => $s->image_path ? Storage::disk('public')->url($s->image_path) : null,
            'cta_url'               => $s->cta_url,
            // Translatable — title
            'title_en'              => $s->getTranslation('title', 'en'),
            'title_pl'              => $s->getTranslation('title', 'pl'),
            'title_pt'              => $s->getTranslation('title', 'pt'),
            // Translatable — short description (listing card)
            'description_en'        => $s->getTranslation('description', 'en') ?? '',
            'description_pl'        => $s->getTranslation('description', 'pl') ?? '',
            'description_pt'        => $s->getTranslation('description', 'pt') ?? '',
            // Translatable — rich body (detail page)
            'body_en'               => $s->getTranslation('body', 'en') ?? '',
            'body_pl'               => $s->getTranslation('body', 'pl') ?? '',
            'body_pt'               => $s->getTranslation('body', 'pt') ?? '',
            // Translatable — badge / eyebrow
            'badge_text_en'         => $s->getTranslation('badge_text', 'en') ?? '',
            'badge_text_pl'         => $s->getTranslation('badge_text', 'pl') ?? '',
            'badge_text_pt'         => $s->getTranslation('badge_text', 'pt') ?? '',
            // Translatable — custom CTA label
            'cta_label_en'          => $s->getTranslation('cta_label', 'en') ?? '',
            'cta_label_pl'          => $s->getTranslation('cta_label', 'pl') ?? '',
            'cta_label_pt'          => $s->getTranslation('cta_label', 'pt') ?? '',
            // Translatable — SEO
            'meta_title_en'         => $s->getTranslation('meta_title', 'en') ?? '',
            'meta_title_pl'         => $s->getTranslation('meta_title', 'pl') ?? '',
            'meta_title_pt'         => $s->getTranslation('meta_title', 'pt') ?? '',
            'meta_description_en'   => $s->getTranslation('meta_description', 'en') ?? '',
            'meta_description_pl'   => $s->getTranslation('meta_description', 'pl') ?? '',
            'meta_description_pt'   => $s->getTranslation('meta_description', 'pt') ?? '',
            // JSON arrays (non-translatable container, locale resolved on frontend)
            'features'              => $s->features ?? [],
            'faq'                   => $s->faq ?? [],
        ];
    }

    public function index(): Response
    {
        $locale = $this->resolveLocale();
        $items  = $this->service->getAll()->map(fn ($s) => $this->mapItem($s))->values();

        return Inertia::render('Services/Index', array_merge($this->sharedSections(), [
            'locale' => $locale,
            'items'  => $items,
        ]));
    }

    public function show(string $slug): Response|\Illuminate\Http\RedirectResponse
    {
        $locale = $this->resolveLocale();
        $item   = $this->service->findBySlug($slug);

        if (! $item) {
            abort(404);
        }

        if (! $item->is_active) {
            return redirect()->route('services.index');
        }

        return Inertia::render('Services/Show', array_merge($this->sharedSections(), [
            'locale' => $locale,
            'item'   => $this->mapItem($item),
        ]));
    }
}
