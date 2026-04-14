<?php

namespace App\Http\Controllers;

use App\Services\Portfolio\PortfolioProjectService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    public function __construct(
        private readonly PortfolioProjectService $service,
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

    private function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        // Absolute path (/images/...) or full URL — use as-is
        if (str_starts_with($path, '/') || str_starts_with($path, 'http')) {
            return $path;
        }
        // Relative path from Filament FileUpload — stored in storage/app/public
        return Storage::disk('public')->url($path);
    }

    private function mapProject(mixed $p): array
    {
        return [
            'client'    => $p->client_name,
            'slug'      => $p->slug,
            'is_active' => (bool) $p->is_active,
            'is_featured' => (bool) $p->is_featured,
            'title_en'  => $p->getTranslation('title', 'en'),
            'title_pl'  => $p->getTranslation('title', 'pl'),
            'title_pt'  => $p->getTranslation('title', 'pt'),
            'tag_en'    => $p->getTranslation('tag', 'en'),
            'tag_pl'    => $p->getTranslation('tag', 'pl'),
            'tag_pt'    => $p->getTranslation('tag', 'pt'),
            'desc_en'   => $p->getTranslation('description', 'en'),
            'desc_pl'   => $p->getTranslation('description', 'pl'),
            'desc_pt'   => $p->getTranslation('description', 'pt'),
            'result_en' => $p->getTranslation('result', 'en'),
            'result_pl' => $p->getTranslation('result', 'pl'),
            'result_pt' => $p->getTranslation('result', 'pt'),
                'image'     => $this->resolveImageUrl($p->image_path),
            'link'      => $p->link,
            'tags'      => array_values((array) (is_array($p->tags) ? $p->tags : json_decode($p->tags ?? '[]', true))),
        ];
    }

    public function index(): Response
    {
        $locale   = $this->resolveLocale();
        $projects = $this->service->getAll()->map(fn ($p) => $this->mapProject($p))->values();

        return Inertia::render('Portfolio/Index', [
            'locale'   => $locale,
            'projects' => $projects,
        ]);
    }

    public function show(string $slug): Response|\Illuminate\Http\RedirectResponse
    {
        $locale  = $this->resolveLocale();
        $project = $this->service->findBySlug($slug);

        if (! $project) {
            abort(404);
        }

        if (! $project->is_active) {
            return redirect()->route('portfolio.index');
        }

        return Inertia::render('Portfolio/Show', [
            'locale'  => $locale,
            'project' => $this->mapProject($project),
        ]);
    }
}
