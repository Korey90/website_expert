<?php

namespace App\Services\Hmrc;

use App\Services\Hmrc\Data\HmrcApiResponse;

class HmrcFraudPreventionValidationSummary
{
    /**
     * @return array{
     *     code: string|null,
     *     spec_version: string|null,
     *     errors: int,
     *     warnings: int,
     *     error_items: array<int, array{code: string|null, headers: array<int, string>, message: string|null}>,
     *     warning_items: array<int, array{code: string|null, headers: array<int, string>, message: string|null}>,
     *     valid: bool,
     *     checked_at: string,
     *     correlation_id: string|null
     * }
     */
    public function fromResponse(HmrcApiResponse $response): array
    {
        $code = $this->safeCode($response->data['code'] ?? null);
        $errors = $this->issues($response->data['errors'] ?? null);
        $warnings = $this->issues($response->data['warnings'] ?? null);

        return [
            'code' => $code,
            'spec_version' => $this->safeCode($response->data['specVersion'] ?? null),
            'errors' => count($errors),
            'warnings' => count($warnings),
            'error_items' => $errors,
            'warning_items' => $warnings,
            'valid' => $code === 'VALID_HEADERS' && $errors === [],
            'checked_at' => now()->toIso8601String(),
            'correlation_id' => $this->safeCorrelationId($response->correlationId),
        ];
    }

    /** @return array<int, array{code: string|null, headers: array<int, string>, message: string|null}> */
    private function issues(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $issues = [];
        foreach (array_slice($value, 0, 100) as $issue) {
            if (! is_array($issue)) {
                continue;
            }

            $headers = is_array($issue['headers'] ?? null)
                ? array_values(array_filter(
                    array_slice($issue['headers'], 0, 50),
                    fn (mixed $header): bool => is_string($header)
                        && preg_match('/^gov-[a-z0-9-]{1,100}$/', $header) === 1,
                ))
                : [];

            $issues[] = [
                'code' => $this->safeCode($issue['code'] ?? null),
                'headers' => $headers,
                'message' => $this->safeMessage($issue['message'] ?? null),
            ];
        }

        return $issues;
    }

    private function safeCode(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9._-]{1,80}$/', $value) === 1
            ? $value
            : null;
    }

    private function safeMessage(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' && strlen($value) <= 1000 && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) !== 1
            ? $value
            : null;
    }

    private function safeCorrelationId(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $value) === 1
            ? $value
            : null;
    }
}
