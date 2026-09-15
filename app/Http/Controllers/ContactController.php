<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Http\Requests\QuickContactRequest;
use App\Models\SiteSection;
use App\Services\Leads\LeadService;
use App\Services\Leads\LeadConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    private function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        // Absolute path (/images/...) or full URL — use as-is
        if (str_starts_with($path, '/') || str_starts_with($path, 'http')) {
            return $path;
        }
        // Relative path from Filament FileUpload — stored in storage/app/public
        return Storage::disk('public')->url($path);
    }

    public function index(): Response
    {
        $supported = array_keys(config('languages'));
        $locale    = session('locale');

        if (! $locale || ! in_array($locale, $supported)) {
            $locale = in_array(request()->getPreferredLanguage($supported), $supported)
                ? request()->getPreferredLanguage($supported)
                : $supported[0];
        }

        App::setLocale($locale);

        $sections = SiteSection::where('is_active', true)->get()->keyBy('key');

        $contact = ($s = $sections->get('contact')) ? [
            'title'    => $s->title,
            'subtitle' => $s->subtitle,
            'extra'    => $s->extra,
        ] : null;

        $footer = ($s = $sections->get('footer')) ? [
            'extra' => $s->extra,
        ] : null;

        return Inertia::render('Contact/Index', compact('contact', 'footer', 'locale'));
    }

    public function aboutUs(): Response
    {
        $supported = array_keys(config('languages'));
        $locale    = session('locale');

        if (! $locale || ! in_array($locale, $supported)) {
            $locale = in_array(request()->getPreferredLanguage($supported), $supported)
                ? request()->getPreferredLanguage($supported)
                : $supported[0];
        }

        App::setLocale($locale);

        $sections = SiteSection::where('is_active', true)->get()->keyBy('key');

        $about = ($s = $sections->get('about')) ? [
            'title'       => $s->title,
            'subtitle'    => $s->subtitle,
            'body'        => $s->body,
            'button_text' => $s->button_text,
            'button_url'  => $s->button_url,
            'image_path'  => $this->resolveImageUrl($s->image_path),
            'extra'       => $s->extra,
        ] : null;

        $footer = ($s = $sections->get('footer')) ? [
            'extra' => $s->extra,
        ] : null;

        return Inertia::render('AboutUs/Index', compact('about', 'footer', 'locale'));
    }

    public function store(ContactRequest $request, LeadService $leadService, LeadConsentService $consentService): JsonResponse
    {
        $data = $request->validated();

        // The form has a single smart "contact" field — detect whether it's an email or a phone number.
        $isEmail = filter_var($data['contact'], FILTER_VALIDATE_EMAIL) !== false;

        $leadService->createFromSource(
            leadData: [
                'email'        => $isEmail ? $data['contact'] : null,
                'phone'        => $isEmail ? null : $data['contact'],
                'name'         => $data['name'] ?? null,
                'project_type' => $data['project_type'] ?? null,
                'source'       => 'contact_form',
                'notes'        => $data['message'] ?? null,
                'calculator_data' => null,
            ],
            sourceData: [
                'type'         => 'contact_form',
                'referrer_url' => $request->header('Referer'),
                'page_url'     => $request->header('Origin'),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'utm_source'   => $request->query('utm_source'),
                'utm_medium'   => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
            ],
            consentData: [
                'given'        => (bool) ($data['gdpr_consent'] ?? false),
                'consent_text' => $consentService->getConsentTextForLocale(app()->getLocale()),
                'source_url'   => $request->header('Referer'),
                'ip_address'   => $request->ip(),
                'locale'       => app()->getLocale(),
            ],
            business: currentBusiness() ?? defaultBusiness(),
        );

        return response()->json(['message' => 'ok'], 201);
    }

    public function quickStore(QuickContactRequest $request, LeadService $leadService, LeadConsentService $consentService): JsonResponse
    {
        $data = $request->validated();

        $formData = array_filter([
            'current_website' => $data['current_website'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $leadService->createFromSource(
            leadData: [
                'email'        => $data['email'] ?? null,
                'phone'        => $data['phone'] ?? null,
                'name'         => $data['name'],
                'source'       => 'service_cta',
                'notes'        => $data['message'],
                'project_type' => $data['service_slug'] ?? null,
                'form_data'    => $formData !== [] ? $formData : null,
            ],
            sourceData: [
                'type'         => 'service_cta',
                'referrer_url' => $request->header('Referer'),
                'page_url'     => $request->header('Origin'),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'utm_source'   => $request->query('utm_source'),
                'utm_medium'   => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
            ],
            consentData: [
                'given'        => (bool) ($data['gdpr_consent'] ?? false),
                'consent_text' => $consentService->getConsentTextForLocale(app()->getLocale()),
                'source_url'   => $request->header('Referer'),
                'ip_address'   => $request->ip(),
                'locale'       => app()->getLocale(),
            ],
            business: currentBusiness() ?? defaultBusiness(),
        );

        return response()->json(['message' => 'ok'], 201);
    }
}

