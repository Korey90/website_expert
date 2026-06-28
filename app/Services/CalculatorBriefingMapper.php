<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Maps Lead.calculator_data (and Lead.form_data) to briefing template question keys.
 *
 * Strategy:
 *   1. camelCase calculator key → snake_case question key (projectType → project_type)
 *   2. Explicit aliases for non-obvious mappings (pages → number_of_pages, etc.)
 *   3. Works for any template — keys are matched dynamically against real question keys.
 */
class CalculatorBriefingMapper
{
    /**
     * Explicit aliases: calculator camelCase key → briefing question snake_case key(s).
     * Add more as new templates/calculators are introduced.
     */
    private const ALIASES = [
        'projectType'  => ['project_type', 'type', 'service_type', 'website_type'],
        'companyName'  => ['company_name', 'company', 'business_name', 'client_name'],
        'contactEmail' => ['email', 'contact_email', 'client_email'],
        'pages'        => ['number_of_pages', 'pages', 'page_count'],
        'design'       => ['design_style', 'design', 'design_type', 'design_preference'],
        'cms'          => ['cms_preference', 'cms', 'cms_type', 'platform'],
        'integrations' => ['integrations', 'required_integrations', 'features'],
        'seoPackage'   => ['seo_package', 'seo', 'seo_plan', 'seo_type'],
        'deadline'     => ['deadline', 'timeline', 'delivery_timeline', 'expected_timeline'],
        'hosting'      => ['hosting', 'hosting_type', 'hosting_preference'],
        'estimateLow'  => ['budget_min', 'budget_from', 'min_budget'],
        'estimateHigh' => ['budget_max', 'budget_to', 'max_budget'],
    ];

    /**
     * Maps source data (calculator_data or form_data) to the structured answers format.
     *
     * @param  array<string, mixed>         $sourceData   Lead.calculator_data or Lead.form_data
     * @param  array<int, array<string, mixed>> $sections Template sections with questions
     * @return array{
     *     answers: array<string, array<string, string>>,
     *     prefilled: array<string>
     * }
     */
    public function map(array $sourceData, array $sections): array
    {
        // Build question lookup: question_key → [sectionKey, questionKey]
        $questionLookup = [];
        foreach ($sections as $section) {
            $sKey = $section['key'] ?? '';
            foreach ($section['questions'] ?? [] as $question) {
                $qKey = $question['key'] ?? '';
                if ($sKey && $qKey) {
                    $questionLookup[$qKey] = [$sKey, $qKey];
                }
            }
        }

        if (empty($questionLookup)) {
            return ['answers' => [], 'prefilled' => []];
        }

        // Resolve mappings: question_key → value from source
        $resolved = [];

        foreach ($sourceData as $calcKey => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $candidateKeys = $this->candidateKeys($calcKey);

            foreach ($candidateKeys as $candidate) {
                if (isset($questionLookup[$candidate]) && !isset($resolved[$candidate])) {
                    $resolved[$candidate] = $this->formatValue($value);
                }
            }
        }

        // Build structured answers + flat prefilled list
        $answers   = [];
        $prefilled = [];

        foreach ($resolved as $qKey => $value) {
            [$sKey] = $questionLookup[$qKey];
            $answers[$sKey][$qKey] = $value;
            $prefilled[]           = "{$sKey}.{$qKey}";
        }

        return ['answers' => $answers, 'prefilled' => $prefilled];
    }

    /**
     * Returns all possible question keys a calculator key could map to.
     *
     * @return array<string>
     */
    private function candidateKeys(string $calcKey): array
    {
        $candidates = [];

        // Explicit aliases first
        if (isset(self::ALIASES[$calcKey])) {
            foreach (self::ALIASES[$calcKey] as $alias) {
                $candidates[] = $alias;
            }
        }

        // Auto snake_case conversion
        $snake = Str::snake($calcKey);
        if (!in_array($snake, $candidates, true)) {
            $candidates[] = $snake;
        }

        // Direct match (already snake)
        if (!in_array($calcKey, $candidates, true)) {
            $candidates[] = $calcKey;
        }

        return $candidates;
    }

    /**
     * Formats a raw value to a display-friendly string.
     */
    private function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_filter($value, fn ($v) => $v !== null && $v !== ''));
        }

        return (string) $value;
    }
}
