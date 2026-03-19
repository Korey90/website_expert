<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $supported = array_keys(config('languages'));
        $locale    = session('locale') ?? $request->getPreferredLanguage($supported) ?? $supported[0];
        if (! in_array($locale, $supported)) {
            $locale = $supported[0];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'locale'            => $locale,
            'available_locales' => config('languages'),
        ];
    }
}
