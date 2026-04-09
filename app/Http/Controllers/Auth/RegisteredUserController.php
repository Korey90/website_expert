<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Services\Business\BusinessService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly BusinessService $businessService,
    ) {}

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('client');

        event(new Registered($user));

        Auth::login($user);

        Client::create([
            'company_name'          => $request->input('company_name') ?: $user->name,
            'primary_contact_name'  => $user->name,
            'primary_contact_email' => $user->email,
            'portal_user_id'        => $user->id,
            'status'                => 'prospect',
            'source'                => 'website',
        ]);

        $this->businessService->createForUser($user, [
            'name' => $request->input('company_name') ?: $user->name,
        ]);

        return redirect(route('portal.dashboard', absolute: false));
    }
}
