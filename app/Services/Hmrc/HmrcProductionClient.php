<?php

namespace App\Services\Hmrc;

final class HmrcProductionClient extends AbstractHmrcVatClient
{
    public function __construct()
    {
        parent::__construct(
            (string) config('hmrc.production.base_url'),
            false,
            (string) config('hmrc.api_version', '1.0'),
            (int) config('hmrc.timeout', 20),
        );
    }
}
