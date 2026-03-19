<?php

namespace App\Http\Controllers;

use App\Models\SiteSection;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        $section = SiteSection::where('key', 'hero')
            ->where('is_active', true)
            ->first();

        $hero = $section ? [
            'title'       => $section->title,
            'subtitle'    => $section->subtitle,
            'button_text' => $section->button_text,
            'button_url'  => $section->button_url,
            'image_path'  => $section->image_path,
            'extra'       => $section->extra,
        ] : null;

        return Inertia::render('Welcome', compact('hero'));
    }
}
