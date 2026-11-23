<?php

namespace App\Data\Finance;

readonly class UniversalCreditCollectionResult
{
    public function __construct(
        public int $discovered,
        public int $created,
        public int $updated,
        public int $unchanged,
        public int $removed,
        public int $markedStale,
    ) {}
}
