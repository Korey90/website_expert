<?php

namespace App\Jobs;

use App\Actions\Domain\GenerateDomainInvoiceAction;
use App\Data\Domain\DomainRegistrationPayload;
use App\Models\DomainContact;
use App\Models\DomainOrder;
use App\Models\DomainRenewal;
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
use Illuminate\Notifications\AnonymousNotifiable;
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

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(private readonly DomainOrder $order) {}

    public function handle(
        DomainRegistrarInterface $registrar,
        DomainOrderService       $orderService,
        DomainPricingService     $pricing,
    ): void {
        // ── 1. Notify client: payment confirmed ──────────────────────────────
        $clientEmail = $this->resolveClientEmail();
        if ($clientEmail) {
            Notification::route('mail', $clientEmail)
                ->notify(new DomainOrderPlacedNotification($this->order));
        }

        // ── 2. Notify admins: new paid order requires processing ──────────────
        $this->notifyAdmins(new DomainOrderAdminNotification($this->order));

        // ── 3. Mark as registering ────────────────────────────────────────────
        $orderService->markAsRegistering($this->order);

        // ── 4. Call registrar ─────────────────────────────────────────────────
        try {
            $contact = DomainContact::where('domain_order_id', $this->order->id)
                ->where('type', 'registrant')
                ->first();

            $payload = new DomainRegistrationPayload(
                domainName:               $this->order->domain_name,
                tld:                      $this->order->tld,
                years:                    $this->order->years,
                registrantFirstName:      $contact?->first_name  ?? '',
                registrantLastName:       $contact?->last_name   ?? '',
                registrantEmail:          $contact?->email       ?? '',
                registrantPhone:          $contact?->phone       ?? '',
                registrantAddressLine1:   $contact?->address_line1 ?? '',
                registrantAddressLine2:   $contact?->address_line2,
                registrantCity:           $contact?->city        ?? '',
                registrantCounty:         $contact?->county,
                registrantPostcode:       $contact?->postcode    ?? '',
                registrantCountryCode:    $contact?->country_code ?? 'GB',
                registrantOrganisation:   $contact?->organisation,
                whoisPrivacy:             true,
                autoRenew:                false,
                nameservers:              [],
            );

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

        // ── 5. Handle result ──────────────────────────────────────────────────
        if (! $result->success) {
            $reason = $result->error ?? 'Unknown registrar error';
            $orderService->failOrder($this->order->fresh(), $reason);
            $this->notifyAdmins(new DomainRegistrationFailedNotification($this->order, $reason));
            return;
        }

        $domain = $orderService->completeOrder(
            order:          $this->order->fresh(),
            providerDomainId: $result->providerId,
            registeredAt:   $result->registeredAt,
            expiresAt:      $result->expiresAt,
        );

        // ── 6. Create upcoming renewal record ─────────────────────────────────
        if ($domain->expires_at) {
            $renewPrice = $pricing->calculateRetailPrice($domain->tld, 1, 'renew');
            DomainRenewal::create([
                'domain_id'    => $domain->id,
                'due_date'     => $domain->expires_at,
                'years'        => 1,
                'status'       => 'pending',
                'retail_price' => $renewPrice,
            ]);
        }

        // ── 7. Auto-generate draft invoice ────────────────────────────────────
        try {
            app(GenerateDomainInvoiceAction::class)->execute($this->order->fresh());
        } catch (\Throwable $e) {
            Log::warning("RegisterDomainJob: failed to generate invoice for order #{$this->order->id}: " . $e->getMessage());
        }

        // ── 8. Notify client: domain registered ───────────────────────────────
        if ($clientEmail) {
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
        User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager', 'super_admin']))
            ->where('business_id', $this->order->business_id)
            ->get()
            ->each(fn (User $u) => $u->notify($notification));
    }
}
