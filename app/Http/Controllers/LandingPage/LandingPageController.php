<?php

namespace App\Http\Controllers\LandingPage;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingPage\StoreLandingPageRequest;
use App\Http\Requests\LandingPage\UpdateLandingPageRequest;
use App\Models\LandingPage;
use App\Services\Business\BusinessProfileService;
use App\Services\LandingPage\LandingPageService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    public function __construct(
        private readonly LandingPageService $service,
        private readonly BusinessProfileService $businessProfileService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LandingPage::class);

        $business = currentBusiness();

        $pages = LandingPage::forBusiness($business)
            ->orderByDesc('created_at')
            ->withCount('leads')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('LandingPages/Index', [
            'pages' => $pages,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', LandingPage::class);

        return Inertia::render('LandingPages/Create', [
            'templates'        => config('landing_pages.templates'),
            'conversionGoals'  => config('landing_pages.conversion_goals'),
        ]);
    }

    public function createWithAi(): Response
    {
        $this->authorize('generateAi', LandingPage::class);

        $business = currentBusiness();
        $profile = $this->businessProfileService->getOrCreate($business);

        return Inertia::render('LandingPages/AiGenerator', [
            'business' => array_merge(
                $business->only(['id', 'name', 'locale']),
                ['logo_url' => $business->logo_url]
            ),
            'profile' => $profile,
            'profileCompletion' => $this->businessProfileService->completion($profile),
            'templates' => config('landing_pages.templates'),
            'conversionGoals' => config('landing_pages.conversion_goals'),
            'sectionTypes' => config('landing_pages.section_types'),
        ]);
    }

    public function store(StoreLandingPageRequest $request): RedirectResponse
    {
        $business = currentBusiness();
        $page     = $this->service->create($request->validated(), $business);

        return redirect()
            ->route('landing-pages.edit', $page)
            ->with('success', __('landing_pages.messages.created'));
    }

    public function show(LandingPage $landingPage): Response
    {
        $this->authorize('view', $landingPage);

        $landingPage->load('sections');

        return Inertia::render('LandingPages/Show', [
            'page' => $landingPage->append(['public_url', 'conversion_rate', 'is_published']),
        ]);
    }

    public function edit(LandingPage $landingPage): Response
    {
        $this->authorize('update', $landingPage);

        $landingPage->load('sections');

        return Inertia::render('LandingPages/Edit', [
            'page'             => $landingPage->append(['public_url', 'conversion_rate', 'is_published']),
            'sectionTypes'     => config('landing_pages.section_types'),
            'conversionGoals'  => config('landing_pages.conversion_goals'),
        ]);
    }

    public function update(UpdateLandingPageRequest $request, LandingPage $landingPage): RedirectResponse
    {
        try {
            $this->service->update($landingPage, $request->validated());
        } catch (DomainException $e) {
            return back()->withErrors(['slug' => $e->getMessage()]);
        }

        return back()->with('success', __('landing_pages.messages.updated'));
    }

    public function destroy(LandingPage $landingPage): RedirectResponse
    {
        $this->authorize('delete', $landingPage);

        $this->service->delete($landingPage);

        return redirect()
            ->route('landing-pages.index')
            ->with('success', __('landing_pages.messages.deleted'));
    }

    public function publish(LandingPage $landingPage): RedirectResponse
    {
        $this->authorize('publish', $landingPage);

        try {
            $this->service->publish($landingPage, request()->user());
        } catch (DomainException $e) {
            return back()->withErrors(['publish' => $e->getMessage()]);
        }

        return back()->with('success', __('landing_pages.messages.published'));
    }

    public function unpublish(LandingPage $landingPage): RedirectResponse
    {
        $this->authorize('publish', $landingPage);

        $this->service->unpublish($landingPage);

        return back()->with('success', __('landing_pages.messages.unpublished'));
    }
}
