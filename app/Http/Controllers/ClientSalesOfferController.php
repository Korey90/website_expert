<?php

namespace App\Http\Controllers;

use App\Models\SalesOffer;
use App\Services\RecaptchaService;
use App\Services\SalesOfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ClientSalesOfferController extends Controller
{
    public function __construct(
        private readonly SalesOfferService $service,
        private readonly RecaptchaService  $recaptcha,
    ) {}

    /**
     * Display the public sales offer view and mark it as viewed.
     */
    public function show(string $token)
    {
        $offer = SalesOffer::with(['lead.client', 'business', 'createdBy'])
            ->where('client_token', $token)
            ->whereIn('status', ['sent', 'viewed', 'converted'])
            ->firstOrFail();

        $this->service->markViewed($offer);

        return Inertia::render('SalesOffer/ClientView', [
            'offer' => [
                'id'           => $offer->id,
                'title'        => $offer->title,
                'body'         => $offer->body,
                'language'     => $offer->language,
                'status'       => $offer->status,
                'sent_at'      => $offer->sent_at?->toISOString(),
                'token'        => $offer->client_token,
                'cta_accepted' => (bool) $offer->cta_clicked_at,
            ],
            'business' => [
                'name' => $offer->business?->name ?? 'Website Expert',
            ],
            'client' => [
                'name'    => $offer->lead?->client?->primary_contact_name ?? '',
                'company' => $offer->lead?->client?->company_name ?? '',
            ],
        ]);
    }

    /**
     * Handle client CTA click — idempotent (can be clicked once per session).
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $offer = SalesOffer::with(['lead.client', 'business'])
            ->where('client_token', $token)
            ->whereIn('status', ['sent', 'viewed', 'converted'])
            ->firstOrFail();

        if (! $this->recaptcha->verify($request->input('recaptcha_token'), 'sales_offer_cta')) {
            return response()->json(['ok' => false, 'error' => 'recaptcha_failed'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->service->acceptCta($offer);

        return response()->json(['ok' => true]);
    }
}
