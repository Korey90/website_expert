<?php

namespace App\Services\AI;

interface AiProvider
{
    /** @return list<list<float|int>> */
    public function embed(string $businessId, string $assistantId, array $texts): array;

    /** @return array<string, mixed> */
    public function selectEvidence(string $businessId, string $assistantId, string $question, array $sources): array;
}
