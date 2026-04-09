<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Client;
use App\Services\Account\AccountDeletionService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user   = $request->user();
        $client = Client::where('portal_user_id', $user->id)
            ->first(['id', 'company_name', 'primary_contact_name']);

        $socialAccounts = $user->socialAccounts()
            ->get(['id', 'provider'])
            ->map(fn ($sa) => ['id' => $sa->id, 'provider' => $sa->provider])
            ->values();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status'          => session('status'),
            'client'          => $client,
            'socialAccounts'  => $socialAccounts,
            'hasPassword'     => ! is_null($user->password),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account (GDPR-compliant).
     *
     * - Users WITH a password must confirm it via current_password.
     * - Social-only users (no password) must check an explicit confirmation checkbox.
     */
    public function destroy(Request $request, AccountDeletionService $deletionService): RedirectResponse
    {
        $user = $request->user();

        if (! is_null($user->password)) {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);
        } else {
            $request->validate([
                'confirmed' => ['required', 'accepted'],
            ]);
        }

        Auth::logout();

        $deletionService->delete($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
