<?php

namespace App\Http\Controllers;

use App\Models\ServiceItem;
use App\Models\SiteSection;
use App\Services\Marketing\ServiceItemService;
use Illuminate\Http\RedirectResponse;
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
        $sections = SiteSection::where('is_active', true)
            ->whereIn('key', ['footer'])
            ->get()
            ->keyBy('key');

        return [
            'footer' => ($s = $sections->get('footer')) ? ['extra' => $s->extra] : null,
        ];
    }

    private function mapItem(ServiceItem $item, string $locale): array
    {
        return $this->service->toPublicArray($item, $locale, withDetail: true);
    }

    public function index(): Response
    {
        $locale = $this->resolveLocale();
        $items = $this->service->getAll()->map(fn (ServiceItem $item) => $this->mapItem($item, $locale))->values();

        return Inertia::render('Services/Index', array_merge($this->sharedSections(), [
            'locale' => $locale,
            'items' => $items,
        ]));
    }

    public function show(string $slug): Response|RedirectResponse
    {
        $locale = $this->resolveLocale();
        $item = $this->service->findBySlug($slug);

        if (! $item) {
            abort(404);
        }

        if (! $item->is_active) {
            return redirect()->route('services.index');
        }

        return Inertia::render('Services/Show', array_merge($this->sharedSections(), [
            'locale' => $locale,
            'item' => $this->mapItem($item, $locale),
        ]));
    }
}
