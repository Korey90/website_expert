<?php

namespace App\Services\Hmrc\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class HmrcFraudPreventionException extends RuntimeException
{
    /** @param array<int, string> $missingHeaders */
    public static function missing(array $missingHeaders): self
    {
        return new self('Required HMRC fraud prevention headers are missing: '.implode(', ', $missingHeaders).'.');
    }

    public static function invalidHeader(): self
    {
        return new self('An HMRC fraud prevention header contains an invalid value.');
    }

    public static function sandboxOnly(): self
    {
        return new self('HMRC fraud prevention header validation is available only in the sandbox.');
    }

    public static function configuration(): self
    {
        return new self('HMRC application credentials are not configured for fraud header validation.');
    }

    public static function invalidTokenResponse(): self
    {
        return new self('HMRC returned an invalid application token response.');
    }

    public static function invalidFeedbackResponse(): self
    {
        return new self('HMRC returned an invalid fraud prevention feedback response.');
    }

    public static function unreachable(): self
    {
        return new self('HMRC fraud prevention validation is currently unreachable.');
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();
        $code = strtoupper((string) data_get($response->json(), 'code', data_get($response->json(), 'error', 'HMRC_FPH_FAILED')));
        $code = preg_match('/^[A-Z0-9_-]{1,80}$/', $code) === 1 ? $code : 'HMRC_FPH_FAILED';

        return new self("HMRC fraud prevention request failed [{$code}] (HTTP {$status}).", $status);
    }
}
