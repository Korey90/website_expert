<?php

namespace App\Actions\Domain;

use App\Models\Domain;
use App\Models\DomainEvent;
use App\Models\DomainRenewal;
use App\Services\Domain\DomainPricingService;
use App\Services\Domain\DomainRegistrarInterface;

class RenewDomainAction
{
    public function __construct(
        private readonly DomainRegistrarInterface $registrar,
        private readonly DomainPricingService     $pricing,
    ) {}

    /**
     * Renew a domain via the registrar, update the domain's expiry date,
     * resolve any pending renewal records, and create the next one.
     *
     * @throws \RuntimeException on registrar failure
     */
    public function execute(Domain $domain, int $years = 1): DomainRenewal
    {
        $result = $this->registrar->renew($domain->full_domain, $years);

        if (! $result->success) {
            throw new \RuntimeException(
                "Renewal failed for {$domain->full_domain}: " . ($result->error ?? 'Unknown error')
            );
        }

        // Update domain expiry (use registrar-returned date if available)
        $newExpiry = $result->newExpiresAt ?? $domain->expires_at?->addYears($years);
        $domain->update(['expires_at' => $newExpiry]);

        // Mark the current pending renewal as completed
        DomainRenewal::where('domain_id', $domain->id)
            ->where('status', 'pending')
            ->update(['status' => 'completed', 'completed_at' => now()]);

        // Create the next renewal record
        $price = $this->pricing->calculateRetailPrice($domain->tld, $years, 'renew') ?? 0.00;

        $nextRenewal = DomainRenewal::create([
            'domain_id'    => $domain->id,
            'due_date'     => $newExpiry,
            'years'        => $years,
            'status'       => 'pending',
            'retail_price' => $price,
        ]);

        DomainEvent::log(
            domainId: $domain->id,
            domainOrderId: null,
            type: 'renewed',
            description: "Domain {$domain->full_domain} renewed for {$years} year(s)",
            payload: [
                'years'          => $years,
                'new_expires_at' => $newExpiry?->toIso8601String(),
            ],
        );

        return $nextRenewal;
    }
}
