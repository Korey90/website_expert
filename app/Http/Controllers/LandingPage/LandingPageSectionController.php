<?php

namespace App\Http\Controllers\LandingPage;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingPage\StoreSectionRequest;
use App\Http\Requests\LandingPage\UpdateSectionRequest;
use App\Models\LandingPage;
use App\Models\LandingPageSection;
use App\Services\LandingPage\LandingPageSectionService;
use App\Services\LandingPage\LandingPageService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LandingPageSectionController extends Controller
{
    public function __construct(
        private readonly LandingPageSectionService $sectionService,
        private readonly LandingPageService $pageService,
    ) {}

    public function store(StoreSectionRequest $request, LandingPage $landingPage): RedirectResponse
    {
        $this->sectionService->create($landingPage, $request->validated());

        return back()->with('success', __('landing_pages.messages.section_added'));
    }

    public function update(UpdateSectionRequest $request, LandingPage $landingPage, LandingPageSection $section): RedirectResponse
    {
        $this->sectionService->update($section, $request->validated());

        return back()->with('success', __('landing_pages.messages.section_updated'));
    }

    public function destroy(LandingPage $landingPage, LandingPageSection $section): RedirectResponse
    {
        $this->authorize('update', $landingPage);

        try {
            $this->sectionService->delete($section);
        } catch (DomainException $e) {
            return back()->withErrors(['section' => $e->getMessage()]);
        }

        return back()->with('success', __('landing_pages.messages.section_deleted'));
    }

    /**
     * Accepts JSON body: { "sections": [1, 5, 3, 2] } (array of section IDs in new order).
     */
    public function reorder(Request $request, LandingPage $landingPage): JsonResponse
    {
        $this->authorize('update', $landingPage);

        $validated = $request->validate([
            'sections'   => ['required', 'array'],
            'sections.*' => ['required', 'integer'],
        ]);

        $this->pageService->reorderSections($landingPage, $validated['sections']);

        return response()->json(['success' => true]);
    }
}
