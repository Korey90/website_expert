<?php

namespace App\Data\Finance;

final readonly class UniversalCreditPreflightResult
{
    /**
     * @param  list<array{code: string, context: array<string, mixed>}>  $errors
     * @param  list<array{code: string, context: array<string, mixed>}>  $warnings
     */
    public function __construct(
        public array $errors,
        public array $warnings,
    ) {}

    public function status(): string
    {
        return $this->errors !== [] ? 'red' : ($this->warnings !== [] ? 'amber' : 'green');
    }

    public function isReady(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status(),
            'error_count' => count($this->errors),
            'warning_count' => count($this->warnings),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
