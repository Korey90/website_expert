<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Portal\BasePortalController;
use App\Http\Requests\Business\UpdateProfileRequest;
use App\Services\Business\BusinessProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BusinessProfileController extends BasePortalController
{
    public function __construct(
        private readonly BusinessProfileService $profileService,
    ) {}

    public function edit(): Response
    {
        $business = currentBusiness();
        $profile  = $this->profileService->getOrCreate($business);

        return Inertia::render('Business/Profile', [
            'profile'      => $profile,
            'business'     => array_merge(
                $business->only(['id', 'name']),
                ['logo_url' => $business->logo_url]
            ),
            'isComplete'   => $this->profileService->isComplete($profile),
            'industries'   => config('business.industries'),
            'tonesOfVoice' => config('business.tones_of_voice'),
            'client'       => $this->clientForUser()?->only('id', 'company_name'),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $profile = $this->profileService->getOrCreate(currentBusiness());
        $this->profileService->update($profile, $request->validated());

        return redirect()->back()->with('success', __('business.profile_saved'));
    }

    public function completion(): JsonResponse
    {
        $profile = $this->profileService->getOrCreate(currentBusiness());

        return response()->json(
            $this->profileService->completion($profile)
        );
    }
}
