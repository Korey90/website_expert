<?php

namespace App\Services\Hmrc;

use InvalidArgumentException;

final class HmrcSandboxScenario
{
    public const SUBMISSION_INVALID_VRN = 'INVALID_VRN';

    public const SUBMISSION_INVALID_PERIOD_KEY = 'INVALID_PERIODKEY';

    public const SUBMISSION_INVALID_PAYLOAD = 'INVALID_PAYLOAD';

    public const SUBMISSION_DUPLICATE = 'DUPLICATE_SUBMISSION';

    public const SUBMISSION_TAX_PERIOD_NOT_ENDED = 'TAX_PERIOD_NOT_ENDED';

    public const SUBMISSION_INSOLVENT_TRADER = 'INSOLVENT_TRADER';

    private const SUBMISSION_SCENARIOS = [
        self::SUBMISSION_INVALID_VRN,
        self::SUBMISSION_INVALID_PERIOD_KEY,
        self::SUBMISSION_INVALID_PAYLOAD,
        self::SUBMISSION_DUPLICATE,
        self::SUBMISSION_TAX_PERIOD_NOT_ENDED,
        self::SUBMISSION_INSOLVENT_TRADER,
    ];

    public static function assertValidSubmissionScenario(?string $scenario): void
    {
        if ($scenario === null || $scenario === '') {
            return;
        }

        if (! in_array($scenario, self::SUBMISSION_SCENARIOS, true)) {
            throw new InvalidArgumentException('Unsupported HMRC sandbox submission scenario.');
        }
    }
}
