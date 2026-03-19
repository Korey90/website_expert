<?php

namespace App\Http\Controllers;

use App\Models\SiteSection;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        $supported = array_keys(config('languages'));
        $locale    = session('locale');

        if (! $locale || ! in_array($locale, $supported)) {
            $locale = in_array(request()->getPreferredLanguage($supported), $supported)
                ? request()->getPreferredLanguage($supported)
                : $supported[0];
        }

        App::setLocale($locale);

        $sections = SiteSection::whereIn('key', ['hero', 'about', 'cta_banner', 'trust_strip', 'testimonials', 'services', 'portfolio', 'cost_calculator', 'navbar', 'contact', 'footer'])
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        $hero = ($s = $sections->get('hero')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'image_path'  => $s->image_path,
            'extra'       => $s->extra,
        ] : null;

        $about = ($s = $sections->get('about')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'body'        => $s->body,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'extra'       => $s->extra,
        ] : null;

        $cta_banner = ($s = $sections->get('cta_banner')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'extra'       => $s->extra,
        ] : null;

        $services = ($s = $sections->get('services')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'extra'       => $s->extra,
        ] : null;

        $portfolio = ($s = $sections->get('portfolio')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'extra'       => $s->extra,
        ] : null;

        $trust_strip = ($s = $sections->get('trust_strip')) ? [
            'title' => $s->title,
            'extra' => $s->extra,
        ] : null;

        $testimonials = ($s = $sections->get('testimonials')) ? [
            'title' => $s->title,
            'extra' => $s->extra,
        ] : null;

        $cost_calculator = ($s = $sections->get('cost_calculator')) ? [
            'title'    => $s->title,
            'subtitle' => $s->subtitle,
            'extra'    => $s->extra,
        ] : null;

        $navbar = ($s = $sections->get('navbar')) ? [
            'extra' => $s->extra,
        ] : null;

        $contact = ($s = $sections->get('contact')) ? [
            'title'    => $s->title,
            'subtitle' => $s->subtitle,
            'extra'    => $s->extra,
        ] : null;

        $footer = ($s = $sections->get('footer')) ? [
            'extra' => $s->extra,
        ] : null;

        return Inertia::render('Welcome', compact('hero', 'about', 'cta_banner', 'trust_strip', 'testimonials', 'services', 'portfolio', 'cost_calculator', 'navbar', 'contact', 'footer'));
    }
}
