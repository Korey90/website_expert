<?php

namespace App\Http\Controllers;

use App\Services\Marketing\ServiceItemService;
use Illuminate\Support\Facades\App;
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

    private function mapItem(mixed $s): array
    {
        return [
            'icon'           => $s->icon,
            'price_from'     => $s->price_from,
            'link'           => $s->link,
            'slug'           => $s->slug,
            'is_active'      => (bool) $s->is_active,
            'is_featured'    => (bool) $s->is_featured,
            'title_en'       => $s->getTranslation('title', 'en'),
            'title_pl'       => $s->getTranslation('title', 'pl'),
            'title_pt'       => $s->getTranslation('title', 'pt'),
            'description_en' => $s->getTranslation('description', 'en') ?? '',
            'description_pl' => $s->getTranslation('description', 'pl') ?? '',
            'description_pt' => $s->getTranslation('description', 'pt') ?? '',
        ];
    }

    public function index(): Response
    {
        $locale = $this->resolveLocale();
        $items  = $this->service->getAll()->map(fn ($s) => $this->mapItem($s))->values();

        return Inertia::render('Services/Index', [
            'locale' => $locale,
            'items'  => $items,
        ]);
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

        return Inertia::render('Services/Show', [
            'locale' => $locale,
            'item'   => $this->mapItem($item),
        ]);
    }
}
