<?php

namespace App\Automation;

interface AutomationActionContract
{
    public function execute(array $action, array $context, string $triggerEvent): void;
}
