<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\UpdateProfileRequest;
use App\Services\Business\BusinessProfileService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly BusinessProfileService $profileService,
    ) {}

    /**
     * Route to the appropriate onboarding step.
     */
    public function index(): RedirectResponse
    {
        $business = currentBusiness();

        if (! $business) {
            return redirect()->route('dashboard');
        }

        $profile = $this->profileService->getOrCreate($business);

        if ($this->profileService->isComplete($profile)) {
            return redirect('/admin');
        }

        return redirect()->route('onboarding.profile');
    }

    public function profile(): Response
    {
        $business = currentBusiness();
        $profile  = $this->profileService->getOrCreate($business);

        return Inertia::render('Onboarding/Profile', [
            'business'     => $business->only(['id', 'name']),
            'profile'      => $profile,
            'step'         => 1,
            'totalSteps'   => 2,
            'industries'   => config('business.industries'),
            'tonesOfVoice' => config('business.tones_of_voice'),
        ]);
    }

    public function saveProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $profile = $this->profileService->getOrCreate(currentBusiness());
        $this->profileService->update($profile, $request->validated());

        return redirect()->route('onboarding.complete');
    }

    public function complete(): Response
    {
        return Inertia::render('Onboarding/Complete', [
            'business' => currentBusiness()->only(['id', 'name']),
        ]);
    }
}
