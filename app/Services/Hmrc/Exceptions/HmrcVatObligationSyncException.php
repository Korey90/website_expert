<?php

namespace App\Services\Hmrc\Exceptions;

use RuntimeException;

class HmrcVatObligationSyncException extends RuntimeException
{
    public static function unauthorized(): self
    {
        return new self('The user is not allowed to synchronise HMRC VAT obligations for this business.');
    }

    public static function connectionRequired(): self
    {
        return new self('Connect HMRC again before synchronising VAT obligations.');
    }

    public static function readScopeRequired(): self
    {
        return new self('The HMRC connection does not include the read:vat scope. Connect HMRC again.');
    }

    public static function invalidResponse(): self
    {
        return new self('HMRC returned an invalid VAT obligations response. No periods were changed.');
    }
}
