<?php

namespace App\Services\LandingPage;

use App\Exceptions\LandingPageGenerationException;

class LandingPageJsonSchemaValidator
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function validateDraft(array $payload): void
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $sections = $payload['sections'] ?? null;

        if ($title === '') {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.title_required'), 'title_required');
        }

        if (! is_array($sections) || count($sections) < 2) {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.too_few_sections'), 'too_few_sections');
        }

        if (count($sections) > (int) config('landing_pages.max_sections_per_page', 20)) {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.too_many_sections'), 'too_many_sections');
        }

        $types = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                throw new LandingPageGenerationException(__('landing_pages.ai.errors.invalid_section_payload'), 'invalid_section_payload');
            }

            $type = (string) ($section['type'] ?? '');
            $types[] = $type;
            $this->validateSection($type, $section['content'] ?? [], $section['settings'] ?? []);
        }

        if (! in_array('hero', $types, true) || ! in_array('form', $types, true)) {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.missing_required_sections'), 'missing_required_sections');
        }
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $settings
     */
    public function validateSection(string $type, array $content, array $settings = []): void
    {
        if (! array_key_exists($type, config('landing_pages.section_types', []))) {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.unsupported_section'), 'unsupported_section');
        }

        match ($type) {
            'hero' => $this->assertRequiredTextFields($content, ['headline', 'subheadline', 'cta_text', 'cta_url']),
            'features' => $this->validateItemsSection($content, 'headline', 'items', 1),
            'testimonials' => $this->validateItemsSection($content, 'headline', 'items', 0),
            'cta' => $this->assertRequiredTextFields($content, ['headline', 'cta_text', 'cta_url']),
            'form' => $this->validateFormSection($content),
            'faq' => $this->validateItemsSection($content, 'headline', 'items', 1),
            'text' => $this->validateTextSection($content),
            'video' => $this->validateVideoSection($content),
            default => null,
        };

        $background = $settings['background'] ?? 'white';
        $padding = $settings['padding'] ?? 'md';

        if (! in_array($background, ['white', 'dark', 'primary', 'gradient'], true)) {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.invalid_settings'), 'invalid_settings');
        }

        if (! in_array($padding, ['sm', 'md', 'lg'], true)) {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.invalid_settings'), 'invalid_settings');
        }
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  list<string>  $fields
     */
    private function assertRequiredTextFields(array $content, array $fields): void
    {
        foreach ($fields as $field) {
            if (trim((string) ($content[$field] ?? '')) === '') {
                throw new LandingPageGenerationException(__('landing_pages.ai.errors.invalid_section_payload'), 'invalid_section_payload');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function validateItemsSection(array $content, string $headlineField, string $itemsField, int $minimum): void
    {
        if (trim((string) ($content[$headlineField] ?? '')) === '') {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.invalid_section_payload'), 'invalid_section_payload');
        }

        $items = $content[$itemsField] ?? null;

        if (! is_array($items) || count($items) < $minimum) {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.invalid_section_payload'), 'invalid_section_payload');
        }
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function validateFormSection(array $content): void
    {
        $this->assertRequiredTextFields($content, ['headline', 'cta_text']);

        $fields = $content['fields'] ?? null;
        $required = $content['required'] ?? null;
        $allowed = ['name', 'email', 'phone', 'message'];

        if (! is_array($fields) || ! is_array($required)) {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.invalid_form_fields'), 'invalid_form_fields');
        }

        foreach ($fields as $field) {
            if (! in_array($field, $allowed, true)) {
                throw new LandingPageGenerationException(__('landing_pages.ai.errors.invalid_form_fields'), 'invalid_form_fields');
            }
        }

        foreach ($required as $field) {
            if (! in_array($field, $fields, true)) {
                throw new LandingPageGenerationException(__('landing_pages.ai.errors.invalid_form_fields'), 'invalid_form_fields');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function validateTextSection(array $content): void
    {
        if (trim((string) ($content['headline'] ?? '')) === '' && trim((string) ($content['html'] ?? '')) === '') {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.invalid_section_payload'), 'invalid_section_payload');
        }
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function validateVideoSection(array $content): void
    {
        if (trim((string) ($content['headline'] ?? '')) === '') {
            throw new LandingPageGenerationException(__('landing_pages.ai.errors.invalid_section_payload'), 'invalid_section_payload');
        }
    }
}