<?php

namespace App\Services\Hmrc;

final class HmrcSandboxClient extends AbstractHmrcVatClient
{
    public function __construct()
    {
        parent::__construct(
            (string) config('hmrc.sandbox.base_url'),
            true,
            (string) config('hmrc.api_version', '1.0'),
            (int) config('hmrc.timeout', 20),
        );
    }
}
