<?php

namespace App\Services;

use App\Mail\SalesOfferCtaAdminMail;
use App\Mail\SalesOfferCtaClientMail;
use App\Mail\SalesOfferMail;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\SalesOffer;
use App\Models\SalesOfferTemplate;
use App\Models\User;
use App\Notifications\SalesOfferCtaNotification;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SalesOfferService
{
    /**
     * Create a draft offer from a template (interpolates placeholders).
     */
    public function createFromTemplate(
        Lead               $lead,
        SalesOfferTemplate $template,
        User               $creator,
    ): SalesOffer {
        $body = $this->interpolate($template->body ?? '', $lead);

        $businessId = $lead->business_id ?? currentBusiness()?->id;

        $offer = SalesOffer::create([
            'business_id' => $businessId,
            'lead_id'     => $lead->id,
            'template_id' => $template->id,
            'created_by'  => $creator->id,
            'title'       => $lead->title . ' — ' . $template->title,
            'language'    => $template->language,
            'body'        => $body,
            'status'      => 'draft',
        ]);

        LeadActivity::log(
            leadId:      $lead->id,
            type:        'offer_created',
            description: "Offer created: {$offer->title}",
            metadata:    ['offer_id' => $offer->id],
            userId:      $creator->id,
        );

        return $offer;
    }

    /**
     * Create a blank draft offer (no template).
     */
    public function createBlank(
        Lead   $lead,
        User   $creator,
        string $title,
        string $language = 'en',
    ): SalesOffer {
        $businessId = $lead->business_id ?? currentBusiness()?->id;

        $offer = SalesOffer::create([
            'business_id' => $businessId,
            'lead_id'     => $lead->id,
            'template_id' => null,
            'created_by'  => $creator->id,
            'title'       => $title,
            'language'    => $language,
            'body'        => '',
            'status'      => 'draft',
        ]);

        LeadActivity::log(
            leadId:      $lead->id,
            type:        'offer_created',
            description: "Blank offer created: {$offer->title}",
            metadata:    ['offer_id' => $offer->id],
            userId:      $creator->id,
        );

        return $offer;
    }

    /**
     * Send a draft offer: generate token, send email, mark sent.
     *
     * @throws \RuntimeException when the lead has no client email
     */
    public function send(SalesOffer $offer): void
    {
        $lead  = $offer->lead()->with('client')->first();
        $email = $lead?->client?->primary_contact_email ?? $lead?->email ?? null;

        if (! $email) {
            throw new \RuntimeException(__('sales_offers.errors.no_client_email'));
        }

        if (! $offer->client_token) {
            $offer->client_token = Str::random(64);
        }

        $offer->status  = 'sent';
        $offer->sent_at = now();
        $offer->save();

        Mail::to($email)->queue(new SalesOfferMail($offer));

        LeadActivity::log(
            leadId:      $offer->lead_id,
            type:        'offer_sent',
            description: "Offer sent to {$email}: {$offer->title}",
            metadata:    ['offer_id' => $offer->id, 'email' => $email],
        );
    }

    /**
     * Mark the offer as viewed (called when client opens the public link).
     * viewed_at is set only once.
     */
    public function markViewed(SalesOffer $offer): void
    {
        if ($offer->viewed_at) {
            return;
        }

        $offer->viewed_at = now();

        if ($offer->status === 'sent') {
            $offer->status = 'viewed';
        }

        $offer->save();

        LeadActivity::log(
            leadId:      $offer->lead_id,
            type:        'offer_viewed',
            description: "Offer viewed by client: {$offer->title}",
            metadata:    ['offer_id' => $offer->id],
        );
    }

    /**
     * Mark the offer as converted (admin action after closing a deal).
     */
    public function convertToQuote(SalesOffer $offer): void
    {
        $offer->update(['status' => 'converted']);

        LeadActivity::log(
            leadId:      $offer->lead_id,
            type:        'offer_converted',
            description: "Offer marked as converted: {$offer->title}",
            metadata:    ['offer_id' => $offer->id],
        );
    }

    /**
     * Handle client CTA click:
     * - log activity
     * - send confirmation email to client
     * - notify all admins/managers via email + in-app notification
     */
    public function acceptCta(SalesOffer $offer): void
    {
        // Idempotent — skip notifications if already accepted
        if ($offer->cta_clicked_at !== null) {
            return;
        }

        $offer->loadMissing(['lead.client', 'business']);

        // Mark as accepted
        $offer->update(['cta_clicked_at' => now()]);

        // Log activity
        LeadActivity::log(
            leadId:      $offer->lead_id,
            type:        'offer_cta_clicked',
            description: "Client clicked CTA on offer: {$offer->title}",
            metadata:    ['offer_id' => $offer->id],
        );

        // Email to client
        $clientEmail = $offer->lead?->client?->primary_contact_email;
        if ($clientEmail) {
            Mail::to($clientEmail)->queue(new SalesOfferCtaClientMail($offer));
        }

        // Notify all admins and managers
        $admins = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager', 'super_admin']))
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(new SalesOfferCtaAdminMail($offer, $admin));
            $admin->notify(new SalesOfferCtaNotification($offer));
            DatabaseNotificationsSent::dispatch($admin);
        }
    }

    // ── Private ────────────────────────────────────────────────────────────

    private function interpolate(string $body, Lead $lead): string
    {
        $lead->loadMissing('client');

        $replacements = [
            '{{client_name}}'   => $lead->client?->primary_contact_name ?? '',
            '{{company_name}}'  => $lead->client?->company_name ?? '',
            '{{lead_title}}'    => $lead->title ?? '',
        ];

        $body = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $body,
        );

        return $this->stripInternalSections($body);
    }

    /**
     * Remove internal-only blockquote sections (Założenia, Pytania otwarte)
     * that should never be visible to clients.
     */
    private function stripInternalSections(string $body): string
    {
        // Remove markdown blockquote blocks starting with **Założenia** or **Pytania otwarte**
        // These are lines starting with '>' that form contiguous blocks.
        $lines  = explode("\n", $body);
        $result = [];
        $skip   = false;

        foreach ($lines as $line) {
            $isBlockquote = str_starts_with(ltrim($line), '>');

            if ($isBlockquote && preg_match('/>\s*\*\*(Założenia|Pytania otwarte)\*\*/', $line)) {
                $skip = true;
            }

            if ($skip && !$isBlockquote && trim($line) !== '') {
                $skip = false;
            }

            if (!$skip) {
                $result[] = $line;
            }
        }

        return rtrim(implode("\n", $result));
    }
}
