<?php

namespace App\Services\AI;

use RuntimeException;

final class AiUnavailable extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
