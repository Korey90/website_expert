<?php

namespace App\Automation;

/**
 * Thrown by automation action classes when the action is intentionally
 * skipped (e.g. missing phone, no template, gate blocked).
 *
 * This allows ProcessAutomationJob to distinguish a real error from
 * a deliberate skip and log the reason accurately.
 */
class ActionSkippedException extends \RuntimeException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }
}
