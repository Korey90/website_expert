<?php

namespace App\Http\Controllers\LandingPage;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingPage\StoreLandingPageRequest;
use App\Http\Requests\LandingPage\UpdateLandingPageRequest;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LandingPage;
use App\Services\Billing\PlanService;
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
        private readonly PlanService $planService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LandingPage::class);

        $business = currentBusiness();

        $landingPages = LandingPage::forBusiness($business)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $statusCounts = LandingPage::forBusiness($business)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $stats = [
            'total'     => $statusCounts->sum(),
            'published' => $statusCounts->get('published', 0),
            'draft'     => $statusCounts->get('draft', 0),
            'archived'  => $statusCounts->get('archived', 0),
        ];

        $client = Client::where('portal_user_id', auth()->id())->first();

        return Inertia::render('LandingPages/Index', [
            'landingPages' => $landingPages,
            'stats'        => $stats,
            'client'       => $client?->only('id', 'company_name'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', LandingPage::class);

        $client = Client::where('portal_user_id', auth()->id())->first();

        return Inertia::render('LandingPages/Create', [
            'templates'        => array_values(config('landing_pages.templates')),
            'conversionGoals'  => config('landing_pages.conversion_goals'),
            'client'           => $client?->only('id', 'company_name'),
        ]);
    }

    public function createWithAi(): Response
    {
        $this->authorize('generateAi', LandingPage::class);

        $business = currentBusiness();
        $profile = $this->businessProfileService->getOrCreate($business);
        $client = Client::where('portal_user_id', auth()->id())->first();

        return Inertia::render('LandingPages/AiGenerator', [
            'business' => array_merge(
                $business->only(['id', 'name', 'locale']),
                ['logo_url' => $business->logo_url]
            ),
            'profile' => $profile,
            'profileCompletion' => $this->businessProfileService->completion($profile),
            'templates' => array_values(config('landing_pages.templates')),
            'conversionGoals' => config('landing_pages.conversion_goals'),
            'sectionTypes' => config('landing_pages.section_types'),
            'client' => $client?->only('id', 'company_name'),
        ]);
    }

    public function store(StoreLandingPageRequest $request): RedirectResponse
    {
        $business = currentBusiness();

        if (! $this->planService->canCreateLandingPage($business)) {
            return back()->withErrors([
                'plan' => __('landing_pages.errors.plan_limit_reached', [
                    'limit' => $this->planService->getLandingPageLimit($business),
                ]),
            ]);
        }

        $page = $this->service->create($request->validated(), $business);

        return redirect()
            ->route('landing-pages.edit', $page)
            ->with('success', __('landing_pages.messages.created'));
    }

    public function show(LandingPage $landingPage): Response
    {
        $this->authorize('view', $landingPage);

        $landingPage->load('sections');

        $recentLeads = Lead::where('landing_page_id', $landingPage->id)
            ->with('client:id,company_name,primary_contact_name,primary_contact_email,primary_contact_phone')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($lead) => [
                'id'              => $lead->id,
                'name'            => $lead->client?->primary_contact_name ?? $lead->client?->company_name,
                'email'           => $lead->client?->primary_contact_email,
                'phone'           => $lead->client?->primary_contact_phone,
                'utm_source'      => $lead->utm_source,
                'created_at'      => $lead->created_at?->format('d M Y H:i'),
            ]);

        $client = Client::where('portal_user_id', auth()->id())->first();

        return Inertia::render('LandingPages/Show', [
            'page'         => $landingPage->append(['public_url', 'conversion_rate', 'is_published']),
            'recentLeads'  => $recentLeads,
            'client'       => $client?->only('id', 'company_name'),
        ]);
    }

    public function edit(LandingPage $landingPage): Response
    {
        $this->authorize('update', $landingPage);

        $landingPage->load('sections');

        $client = Client::where('portal_user_id', auth()->id())->first();

        return Inertia::render('LandingPages/Edit', [
            'page'             => $landingPage->append(['public_url', 'conversion_rate', 'is_published']),
            'sectionTypes'     => config('landing_pages.section_types'),
            'conversionGoals'  => config('landing_pages.conversion_goals'),
            'client'           => $client?->only('id', 'company_name'),
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
