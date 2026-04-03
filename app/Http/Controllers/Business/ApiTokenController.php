<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Services\Leads\ApiTokenService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiTokenController extends Controller
{
    public function __construct(private readonly ApiTokenService $tokenService) {}

    public function index()
    {
        $business = currentBusiness();

        $tokens = ApiToken::where('business_id', $business->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ApiToken $t) => [
                'id'           => $t->id,
                'name'         => $t->name,
                'is_active'    => $t->is_active,
                'last_used_at' => $t->last_used_at?->diffForHumans(),
                'expires_at'   => $t->expires_at?->toDateString(),
                'created_at'   => $t->created_at->toDateString(),
            ]);

        return Inertia::render('Business/ApiTokens', [
            'tokens' => $tokens,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->tokenService->create(
            business:   currentBusiness(),
            name:       $request->name,
            createdBy:  $request->user(),
        );

        return redirect()->back()->with([
            'success'   => 'API token created.',
            'new_token' => $result['token'], // displayed ONCE — user must copy it
        ]);
    }

    public function destroy(ApiToken $token)
    {
        // Ensure the token belongs to the current business
        abort_unless($token->business_id === currentBusiness()->id, 403);

        $this->tokenService->revoke($token);

        return redirect()->back()->with('success', 'API token revoked.');
    }
}
