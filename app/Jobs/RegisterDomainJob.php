<?php

namespace App\Jobs;

use App\Actions\Domain\EnsureOpHandleAction;
use App\Actions\Domain\GenerateDomainInvoiceAction;
use App\Data\Domain\DomainRegistrationPayload;
use App\Models\DomainContact;
use App\Models\DomainOrder;
use App\Models\DomainRenewal;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\DomainOrderAdminNotification;
use App\Notifications\DomainOrderPlacedNotification;
use App\Notifications\DomainRegisteredNotification;
use App\Notifications\DomainRegistrationFailedNotification;
use App\Services\Domain\DomainOrderService;
use App\Services\Domain\DomainPricingService;
use App\Services\Domain\DomainRegistrarInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Processes a paid domain order:
 *   1. Notifies the client and admin that payment was received.
 *   2. Calls the registrar to register/transfer/renew the domain.
 *   3. On success → completes the order, creates a DomainRenewal, notifies the client.
 *   4. On failure → marks the order as failed, notifies admins.
 *
 * Dispatched by DomainOrderService::markAsPaid().
 */
class RegisterDomainJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(private readonly DomainOrder $order) {}

    public function handle(
        DomainRegistrarInterface $registrar,
        DomainOrderService $orderService,
        DomainPricingService $pricing,
    ): void {
        // ── 1. Notify client: payment confirmed ──────────────────────────────
        $clientEmail = $this->resolveClientEmail();
        Log::info("RegisterDomainJob: resolved client email for order #{$this->order->id}: ".($clientEmail ?? 'NULL'));
        if ($clientEmail) {
            Log::info("RegisterDomainJob: sending payment received notification to client for order #{$this->order->id}");
            Notification::route('mail', $clientEmail)
                ->notify(new DomainOrderPlacedNotification($this->order));
        }

        // ── 2. Notify admins: new paid order requires processing ──────────────
        Log::info("RegisterDomainJob: notifying admins for order #{$this->order->id}");
        $this->notifyAdmins(new DomainOrderAdminNotification($this->order));

        // ── 3. Mark as registering ────────────────────────────────────────────
        Log::info("RegisterDomainJob: marking order #{$this->order->id} as registering");
        $orderService->markAsRegistering($this->order);

        // ── 4. Create invoice after payment and mark it as paid ──────────────
        try {
            Log::debug("RegisterDomainJob step4: generating invoice for order #{$this->order->id}");
            // Re-fetch from DB to guarantee we have the latest stripe_payment_intent_id,
            // regardless of how the model was serialised/restored in the queue payload.
            $freshOrder = DomainOrder::withoutGlobalScopes()->findOrFail($this->order->id);
            $invoice = app(GenerateDomainInvoiceAction::class)->execute($freshOrder);

            Log::debug("RegisterDomainJob step4: order #{$freshOrder->id} pi='{$freshOrder->stripe_payment_intent_id}' invoice #{$invoice->id} status='{$invoice->status}'");

            // The order was paid via Stripe — mark the invoice as paid immediately.
            // The webhook handler only marks it if the invoice already existed at webhook time;
            // since the invoice is created here (after the job is dispatched by the webhook),
            // we must do it here instead.
            if ($invoice->status !== 'paid' && $freshOrder->stripe_payment_intent_id) {
                // Payment must match the VAT-inclusive (gross) amount that Stripe charged
                $grossAmount = round((float) $freshOrder->retail_price * 1.2, 2);
                Payment::firstOrCreate(
                    ['stripe_payment_intent_id' => $freshOrder->stripe_payment_intent_id],
                    [
                        'invoice_id' => $invoice->id,
                        'amount' => $grossAmount,
                        'currency' => strtoupper($freshOrder->currency ?? 'GBP'),
                        'method' => 'stripe',
                        'status' => 'completed',
                        'reference' => $freshOrder->stripe_payment_intent_id,
                        'paid_at' => now(),
                    ]
                );

                $invoice->recalculate();
                $invoice->update(['status' => 'paid', 'paid_at' => now()]);

                Log::info("RegisterDomainJob: invoice #{$invoice->id} marked as paid for order #{$freshOrder->id}");
            } else {
                Log::debug("RegisterDomainJob step4 skipped: invoice_status='{$invoice->status}' pi='".($freshOrder->stripe_payment_intent_id ?: 'NULL')."'");
            }
        } catch (\Throwable $e) {
            Log::warning("RegisterDomainJob: failed to generate/mark invoice for order #{$this->order->id}: ".$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
        }

        Log::info("RegisterDomainJob: starting registrar process for order #{$this->order->id} (domain: {$this->order->full_domain}, action: {$this->order->action})");
        // ── 5. Call registrar ─────────────────────────────────────────────────
        try {
            Log::info("RegisterDomainJob: sending registration request to registrar for order #{$this->order->id}");
            $contact = DomainContact::where('domain_order_id', $this->order->id)
                ->where('type', 'registrant')
                ->first();

            $ownerHandle = null;
            Log::info("RegisterDomainJob: checking if OP handle is needed for order #{$this->order->id} (client_id={$this->order->client_id}, registrar=".config('services.domain_registrar.provider').')');

            if ($this->order->client_id
                && config('services.domain_registrar.provider') === 'openprovider'
                && $this->order->client
            ) {
                Log::info("RegisterDomainJob: ensuring OP handle for client_id={$this->order->client_id} (order #{$this->order->id})");
                $ownerHandle = app(EnsureOpHandleAction::class)->execute(
                    $this->order->client,
                    [
                        'email' => $contact?->email ?? '',
                        'first_name' => $contact?->first_name ?? '',
                        'last_name' => $contact?->last_name ?? '',
                        'phone' => $contact?->phone ?? '',
                        'country_code' => $contact?->country_code ?? 'GB',
                        'address_line1' => $contact?->address_line1 ?? '',
                        'address_line2' => $contact?->address_line2,
                        'city' => $contact?->city ?? '',
                        'county' => $contact?->county,
                        'postcode' => $contact?->postcode ?? '',
                        'organisation' => $contact?->organisation,
                    ]
                );
            }

            Log::info("RegisterDomainJob: OP handle for order #{$this->order->id}: ".($ownerHandle ?? 'NULL'));

            $payload = new DomainRegistrationPayload(
                domainName: $this->order->domain_name,
                tld: $this->order->tld,
                years: $this->order->years,
                registrantFirstName: $contact?->first_name ?? '',
                registrantLastName: $contact?->last_name ?? '',
                registrantEmail: $contact?->email ?? '',
                registrantPhone: $contact?->phone ?? '',
                registrantAddressLine1: $contact?->address_line1 ?? '',
                registrantAddressLine2: $contact?->address_line2,
                registrantCity: $contact?->city ?? '',
                registrantCounty: $contact?->county,
                registrantPostcode: $contact?->postcode ?? '',
                registrantCountryCode: $contact?->country_code ?? 'GB',
                registrantOrganisation: $contact?->organisation,
                whoisPrivacy: true,
                autoRenew: false,
                nameservers: [],
                ownerHandle: $ownerHandle,
            );

            Log::info("RegisterDomainJob: sending registration request to registrar for {$payload->domainName}. Payload: ".json_encode($payload));
            $result = $registrar->register($payload);

        } catch (\Throwable $e) {
            $reason = $e->getMessage();
            Log::error("RegisterDomainJob: registrar exception for order #{$this->order->id}", [
                'exception' => $reason,
            ]);
            $orderService->failOrder($this->order->fresh(), $reason);
            $this->notifyAdmins(new DomainRegistrationFailedNotification($this->order, $reason));

            return;
        }

        // ── 6. Handle result ──────────────────────────────────────────────────
        if (! $result->success) {
            Log::error("RegisterDomainJob: registrar error for order #{$this->order->id}: ".($result->error ?? 'Unknown error'));
            $reason = $result->error ?? 'Unknown registrar error';
            $orderService->failOrder($this->order->fresh(), $reason);
            $this->notifyAdmins(new DomainRegistrationFailedNotification($this->order, $reason));

            return;
        }

        $domain = $orderService->completeOrder(
            order: $this->order->fresh(),
            providerDomainId: $result->providerId,
            registeredAt: $result->registeredAt,
            expiresAt: $result->expiresAt,
        );
        Log::info("zawartosc domain: {$domain}");

        // ── 7. Create upcoming renewal record ─────────────────────────────────
        if ($domain->expires_at) {
            Log::info("RegisterDomainJob: creating renewal record for {$domain->full_domain} due on {$domain->expires_at->toDateString()}");
            $currency = $pricing->resolveCurrency($this->order->currency ?? null);
            $snapshot = $pricing->getPriceForTld($domain->tld, $currency);
            $currency = $snapshot?->currency ?? $currency;
            $renewPrice = $pricing->calculateRetailPrice($domain->tld, 1, 'renew', $currency);
            DomainRenewal::create([
                'domain_id' => $domain->id,
                'due_date' => $domain->expires_at,
                'years' => 1,
                'status' => 'pending',
                'retail_price' => $renewPrice,
                'currency' => $currency,
            ]);
        }

        // ── 8. Notify client: domain registered ───────────────────────────────
        if ($clientEmail) {
            Log::info("RegisterDomainJob: sending domain registered notification to client for order #{$this->order->id}");
            Notification::route('mail', $clientEmail)
                ->notify(new DomainRegisteredNotification($domain));
        }

        Log::info("RegisterDomainJob: completed for {$domain->full_domain} (order #{$this->order->id})");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveClientEmail(): ?string
    {
        // Try the registrant contact first (they provided the email at checkout)
        $contact = DomainContact::where('domain_order_id', $this->order->id)
            ->where('type', 'registrant')
            ->value('email');

        if ($contact) {
            return $contact;
        }

        // Fallback: client record
        return $this->order->client?->primary_contact_email;
    }

    private function notifyAdmins(mixed $notification): void
    {
        try {
            User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager', 'super_admin']))
                ->whereHas('businesses', function ($q) {
                    $q->where('businesses.id', $this->order->business_id)
                        ->where('business_users.is_active', true);
                })
                ->get()
                ->each(fn (User $u) => $u->notify($notification));
        } catch (\Throwable $e) {
            // Notifications should never block core domain registration flow.
            Log::warning("RegisterDomainJob: admin notification failed for order #{$this->order->id}", [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
