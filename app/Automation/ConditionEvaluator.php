<?php

namespace App\Automation;

class ConditionEvaluator
{
    public function evaluate(array $conditions, array $context): bool
    {
        foreach ($conditions as $condition) {
            $field        = $condition['field']    ?? null;
            $operator     = $condition['operator'] ?? '=';
            $value        = $condition['value']    ?? null;
            $contextValue = $context[$field]       ?? null;

            $passes = match ($operator) {
                '='        => $contextValue == $value,
                '!='       => $contextValue != $value,
                '>'        => $contextValue > $value,
                '<'        => $contextValue < $value,
                '>='       => $contextValue >= $value,
                '<='       => $contextValue <= $value,
                'contains' => str_contains((string) $contextValue, (string) $value),
                default    => true,
            };

            if (! $passes) {
                return false;
            }
        }

        return true;
    }
}
