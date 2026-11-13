<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchBusinessController extends Controller
{
    public function __invoke(Request $request, Business $business): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $business->is_active
                && $user->businessMemberships()
                    ->where('business_id', $business->id)
                    ->where('is_active', true)
                    ->exists(),
            403,
        );

        $request->session()->put(User::CURRENT_BUSINESS_SESSION_KEY, $business->id);

        return redirect('/admin');
    }
}
