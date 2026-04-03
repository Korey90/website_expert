<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\UpdateBusinessRequest;
use App\Services\Business\BusinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function __construct(
        private readonly BusinessService $businessService,
    ) {}

    public function edit(): Response
    {
        $business = currentBusiness()->load('profile');

        return Inertia::render('Business/Settings', [
            'business' => array_merge(
                $business->only(['id', 'name', 'locale', 'timezone', 'primary_color', 'plan']),
                ['logo_url' => $business->logo_url]
            ),
            'profile' => $business->profile,
        ]);
    }

    public function update(UpdateBusinessRequest $request): RedirectResponse
    {
        $this->businessService->update(currentBusiness(), $request->validated());

        return redirect()->back()->with('success', __('business.settings_saved'));
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $path = $this->businessService->uploadLogo(
            currentBusiness(),
            $request->file('logo')
        );

        return response()->json([
            'logo_url' => Storage::disk('public')->url($path),
        ]);
    }

    public function deleteLogo(): RedirectResponse
    {
        $this->businessService->deleteLogo(currentBusiness());

        return redirect()->back()->with('success', __('business.logo_deleted'));
    }
}
