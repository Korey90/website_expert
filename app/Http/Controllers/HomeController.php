<?php

namespace App\Http\Controllers;

use App\Models\SiteSection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $hero = SiteSection::where('key', 'hero')
            ->where('is_active', true)
            ->first();

        return Inertia::render('Welcome', [
            'hero' => $hero,
        ]);
    }
}
