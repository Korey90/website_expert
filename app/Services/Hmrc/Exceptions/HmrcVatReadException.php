<?php

namespace App\Services\Hmrc\Exceptions;

use RuntimeException;

class HmrcVatReadException extends RuntimeException
{
    public static function unauthorized(): self
    {
        return new self('The user is not allowed to retrieve HMRC VAT data for this business.');
    }

    public static function connectionRequired(): self
    {
        return new self('Connect HMRC again before retrieving VAT data.');
    }

    public static function readScopeRequired(): self
    {
        return new self('The HMRC connection does not include the read:vat scope. Connect HMRC again.');
    }

    public static function obligationRequired(): self
    {
        return new self('Select a fulfilled VAT obligation for the current business and environment.');
    }

    public static function returnNotFound(): self
    {
        return new self(__('finance.hmrc_connection.errors.return_not_found'));
    }

    public static function invalidReturnResponse(): self
    {
        return new self('HMRC returned an invalid VAT Return response. No result was displayed.');
    }

    public static function invalidLiabilitiesResponse(): self
    {
        return new self('HMRC returned an invalid VAT liabilities response. No result was displayed.');
    }

    public static function invalidPaymentsResponse(): self
    {
        return new self('HMRC returned an invalid VAT payments response. No result was displayed.');
    }
}
