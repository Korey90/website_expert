<?php

namespace App\Actions\Domain;

use App\Models\Domain;
use App\Models\DomainEvent;
use App\Services\Domain\DomainRegistrarInterface;

class SaveDnsRecordAction
{
    public function __construct(private readonly DomainRegistrarInterface $registrar) {}

    /**
     * Create (recordId=null) or update an existing DNS record.
     * For update, recordId is the array index from the cached dns_records.
     * Returns the record data with id.
     */
    public function execute(Domain $domain, ?int $recordId, array $data): array
    {
        if ($recordId === null) {
            $result = $this->registrar->createDnsRecord($domain->full_domain, $data);
            $type   = 'dns_record_created';
        } else {
            $cached   = $domain->dns_records ?? [];
            $original = $cached[$recordId] ?? null;

            if ($original) {
                $cleanOriginal = collect($original)->except('id')->toArray();
                $this->registrar->updateDnsRecord($domain->full_domain, $cleanOriginal, $data);
            }

            $result = array_merge(['id' => $recordId], $data);
            $type   = 'dns_record_updated';
        }

        DomainEvent::log(
            domainId:      $domain->id,
            domainOrderId: null,
            type:          $type,
            description:   ucfirst(str_replace('_', ' ', $type)) . " for {$domain->full_domain}",
            payload:       array_merge(['record_id' => $result['id'] ?? $recordId], $data),
        );

        app(FetchDnsRecordsAction::class)->execute($domain);

        return $result;
    }
}
