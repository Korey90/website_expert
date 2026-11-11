<?php

namespace App\Services\Hmrc;

use App\Services\Hmrc\Data\HmrcApiResponse;
use App\Services\Hmrc\Exceptions\HmrcFraudPreventionException;

final class HmrcFraudPreventionFeedbackSummary
{
    /** @return array{request_count: int, checked_at: string, correlation_id: string|null} */
    public function fromResponse(HmrcApiResponse $response): array
    {
        $requests = $response->data['requests'] ?? null;

        if (! is_array($requests) || ! array_is_list($requests) || count($requests) > 1000) {
            throw HmrcFraudPreventionException::invalidFeedbackResponse();
        }

        return [
            'request_count' => count($requests),
            'checked_at' => now()->toIso8601String(),
            'correlation_id' => is_string($response->correlationId)
                && preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $response->correlationId) === 1
                    ? $response->correlationId
                    : null,
        ];
    }
}
