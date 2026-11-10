<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Services\Hmrc\Exceptions\HmrcOAuthException;
use App\Services\Hmrc\HmrcOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HmrcOAuthController extends Controller
{
    public function __construct(
        private readonly HmrcOAuthService $oauth,
    ) {}

    public function connect(Request $request): RedirectResponse
    {
        [$business, $actor] = $this->context($request);

        try {
            return redirect()->away($this->oauth->authorizationUrl($request, $business, $actor));
        } catch (HmrcOAuthException $exception) {
            return $this->failure($exception);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        [$business, $actor] = $this->context($request);

        try {
            $this->oauth->completeAuthorization($request, $business, $actor);
        } catch (HmrcOAuthException $exception) {
            return $this->failure($exception);
        }

        return $this->destination()->with('success', 'HMRC connection established successfully.');
    }

    public function refresh(Request $request): RedirectResponse
    {
        [$business, $actor] = $this->context($request);

        try {
            $this->oauth->refresh($business, $actor);
        } catch (HmrcOAuthException $exception) {
            return $this->failure($exception);
        }

        return $this->destination()->with('success', 'HMRC access token refreshed successfully.');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        [$business, $actor] = $this->context($request);
        $this->oauth->disconnect($business, $actor);

        return $this->destination()->with(
            'success',
            'HMRC tokens were removed locally. Revoke the authority separately in HMRC Manage authorised applications if required.',
        );
    }

    /** @return array{Business, User} */
    private function context(Request $request): array
    {
        $business = currentBusiness();
        $actor = $request->user();

        abort_unless($business instanceof Business && $actor instanceof User, 404);

        $canManage = $actor->businessMemberships()
            ->where('business_id', $business->getKey())
            ->where('is_active', true)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();

        abort_unless($canManage, 403);

        return [$business, $actor];
    }

    private function destination(): RedirectResponse
    {
        return redirect()->route('filament.admin.resources.vat-returns.index');
    }

    private function failure(HmrcOAuthException $exception): RedirectResponse
    {
        return $this->destination()->with('error', $exception->getMessage());
    }
}
