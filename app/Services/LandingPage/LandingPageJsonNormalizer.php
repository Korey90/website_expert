<?php

namespace App\Services\LandingPage;

use App\Exceptions\LandingPageGenerationException;
use App\Models\LandingPageSection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LandingPageJsonNormalizer
{
    /**
     * @return array<string, mixed>
     */
    public function normalize(array $raw): array
    {
        $title = $this->cleanText((string) ($raw['title'] ?? 'AI Landing Page'), 255);
        $sections = collect($raw['sections'] ?? [])
            ->filter(fn ($section) => is_array($section) && isset($section['type']))
            ->map(fn (array $section) => $this->normalizeSection($section))
            ->all();

        $sections = $this->sortSections($this->ensureRequiredSections($sections, $title));

        return [
            'title' => $title,
            'slug_suggestion' => $this->normalizeSlug($raw['slug_suggestion'] ?? $title),
            'language' => $this->normalizeLanguage($raw['language'] ?? 'en'),
            'template_key' => $this->normalizeTemplateKey($raw['template_key'] ?? null),
            'meta' => [
                'meta_title' => $this->cleanText((string) Arr::get($raw, 'meta.meta_title', $title), 160),
                'meta_description' => $this->cleanText((string) Arr::get($raw, 'meta.meta_description', $title), 320),
                'conversion_goal' => $this->normalizeGoal(Arr::get($raw, 'meta.conversion_goal', 'contact')),
            ],
            'sections' => array_values($sections),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeSection(array $section): array
    {
        $type = (string) ($section['type'] ?? '');

        if (! array_key_exists($type, config('landing_pages.section_types', []))) {
            throw new LandingPageGenerationException(
                __('landing_pages.ai.errors.unsupported_section'),
                'unsupported_section',
                422,
            );
        }

        $content = is_array($section['content'] ?? null) ? $section['content'] : [];
        $settings = is_array($section['settings'] ?? null) ? $section['settings'] : [];

        return [
            'type' => $type,
            'content' => $this->normalizeSectionContent($type, $content),
            'settings' => $this->normalizeSectionSettings($type, $settings),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultSection(string $type): array
    {
        $model = new LandingPageSection();

        return [
            'type' => $type,
            'content' => $model->getDefaultContent($type),
            'settings' => $this->normalizeSectionSettings($type, []),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function normalizeSectionContent(string $type, array $content): array
    {
        return match ($type) {
            'hero' => [
                'headline' => $this->cleanText((string) ($content['headline'] ?? 'Grow with a focused landing page'), 140),
                'subheadline' => $this->cleanText((string) ($content['subheadline'] ?? 'Turn more visitors into qualified leads.'), 280),
                'cta_text' => $this->cleanText((string) ($content['cta_text'] ?? 'Get Started'), 60),
                'cta_url' => (string) ($content['cta_url'] ?? '#form'),
                'image_path' => $content['image_path'] ?? null,
            ],
            'features' => [
                'headline' => $this->cleanText((string) ($content['headline'] ?? 'Why choose us?'), 140),
                'items' => array_values(array_slice(array_map(function ($item) {
                    return [
                        'icon' => $this->cleanText((string) ($item['icon'] ?? 'check'), 30),
                        'title' => $this->cleanText((string) ($item['title'] ?? 'Benefit'), 80),
                        'description' => $this->cleanText((string) ($item['description'] ?? ''), 220),
                    ];
                }, is_array($content['items'] ?? null) ? $content['items'] : []), 0, 6)),
            ],
            'testimonials' => [
                'headline' => $this->cleanText((string) ($content['headline'] ?? 'Trusted by clients'), 140),
                'items' => array_values(array_slice(array_map(function ($item) {
                    return [
                        'author' => $this->cleanText((string) ($item['author'] ?? ''), 80),
                        'company' => $this->cleanText((string) ($item['company'] ?? ''), 120),
                        'text' => $this->cleanText((string) ($item['text'] ?? ''), 320),
                        'rating' => min(max((int) ($item['rating'] ?? 5), 1), 5),
                        'avatar_path' => $item['avatar_path'] ?? null,
                    ];
                }, is_array($content['items'] ?? null) ? $content['items'] : []), 0, 6)),
            ],
            'cta' => [
                'headline' => $this->cleanText((string) ($content['headline'] ?? 'Ready to take the next step?'), 140),
                'subheadline' => $this->cleanText((string) ($content['subheadline'] ?? 'Let us help you move faster.'), 220),
                'cta_text' => $this->cleanText((string) ($content['cta_text'] ?? 'Contact us'), 60),
                'cta_url' => (string) ($content['cta_url'] ?? '#form'),
            ],
            'form' => [
                'headline' => $this->cleanText((string) ($content['headline'] ?? 'Get in touch'), 140),
                'subheadline' => $this->cleanText((string) ($content['subheadline'] ?? 'We will get back to you shortly.'), 220),
                'fields' => $this->normalizeFormFields($content['fields'] ?? ['name', 'email', 'phone', 'message']),
                'required' => $this->normalizeFormRequired($content['required'] ?? ['name', 'email']),
                'cta_text' => $this->cleanText((string) ($content['cta_text'] ?? 'Send'), 60),
                'success_message' => $this->cleanText((string) ($content['success_message'] ?? 'Thank you, we will contact you shortly.'), 220),
                'redirect_url' => $content['redirect_url'] ?? null,
            ],
            'faq' => [
                'headline' => $this->cleanText((string) ($content['headline'] ?? 'Frequently asked questions'), 140),
                'items' => array_values(array_slice(array_map(function ($item) {
                    return [
                        'question' => $this->cleanText((string) ($item['question'] ?? ''), 180),
                        'answer' => $this->cleanText((string) ($item['answer'] ?? ''), 400),
                    ];
                }, is_array($content['items'] ?? null) ? $content['items'] : []), 0, 8)),
            ],
            'text' => [
                'headline' => $this->cleanText((string) ($content['headline'] ?? ''), 140),
                'html' => $this->sanitizeHtml((string) ($content['html'] ?? '<p>Tell your story here.</p>')),
            ],
            'video' => [
                'headline' => $this->cleanText((string) ($content['headline'] ?? 'See how it works'), 140),
                'video_url' => $this->cleanText((string) ($content['video_url'] ?? ''), 500),
                'autoplay' => (bool) ($content['autoplay'] ?? false),
                'thumbnail_path' => $content['thumbnail_path'] ?? null,
            ],
            default => throw new LandingPageGenerationException(__('landing_pages.ai.errors.unsupported_section'), 'unsupported_section', 422),
        };
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function normalizeSectionSettings(string $type, array $settings): array
    {
        $background = (string) ($settings['background'] ?? $this->defaultBackgroundForType($type));
        $padding = (string) ($settings['padding'] ?? 'md');

        if (! in_array($background, ['white', 'dark', 'primary', 'gradient'], true)) {
            $background = $this->defaultBackgroundForType($type);
        }

        if (! in_array($padding, ['sm', 'md', 'lg'], true)) {
            $padding = 'md';
        }

        return [
            'background' => $background,
            'padding' => $padding,
            'visible' => (bool) ($settings['visible'] ?? true),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private function ensureRequiredSections(array $sections, string $title): array
    {
        $types = array_column($sections, 'type');

        if (! in_array('hero', $types, true)) {
            $hero = $this->defaultSection('hero');
            $hero['content']['headline'] = $title;
            $sections[] = $hero;
        }

        if (! in_array('form', $types, true)) {
            $sections[] = $this->defaultSection('form');
        }

        return $sections;
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private function sortSections(array $sections): array
    {
        $order = [
            'hero' => 1,
            'features' => 2,
            'testimonials' => 3,
            'cta' => 4,
            'faq' => 5,
            'text' => 6,
            'video' => 7,
            'form' => 8,
        ];

        usort($sections, fn (array $a, array $b) => ($order[$a['type']] ?? 99) <=> ($order[$b['type']] ?? 99));

        return $sections;
    }

    private function normalizeSlug(mixed $value): string
    {
        $slug = Str::slug((string) $value);

        return $slug !== '' ? Str::limit($slug, 100, '') : 'ai-landing-page';
    }

    private function normalizeLanguage(mixed $value): string
    {
        $language = (string) $value;

        return in_array($language, ['en', 'pl', 'pt'], true) ? $language : 'en';
    }

    private function normalizeTemplateKey(mixed $value): ?string
    {
        $template = is_string($value) ? $value : null;

        return $template && array_key_exists($template, config('landing_pages.templates', [])) ? $template : null;
    }

    private function normalizeGoal(mixed $value): string
    {
        $goal = (string) $value;
        $allowed = array_keys(config('landing_pages.conversion_goals', []));

        return in_array($goal, $allowed, true) ? $goal : 'contact';
    }

    /**
     * @return list<string>
     */
    private function normalizeFormFields(mixed $fields): array
    {
        $allowed = ['name', 'email', 'phone', 'message'];
        $fields = is_array($fields) ? $fields : ['name', 'email', 'phone', 'message'];
        $fields = array_values(array_intersect($fields, $allowed));

        return $fields !== [] ? $fields : ['name', 'email', 'phone', 'message'];
    }

    /**
     * @return list<string>
     */
    private function normalizeFormRequired(mixed $required): array
    {
        $allowed = ['name', 'email', 'phone', 'message'];
        $required = is_array($required) ? $required : ['name', 'email'];
        $required = array_values(array_intersect($required, $allowed));

        if (! in_array('name', $required, true)) {
            $required[] = 'name';
        }

        if (! in_array('email', $required, true)) {
            $required[] = 'email';
        }

        return array_values(array_unique($required));
    }

    private function defaultBackgroundForType(string $type): string
    {
        return match ($type) {
            'hero' => 'gradient',
            'cta' => 'primary',
            'testimonials' => 'dark',
            default => 'white',
        };
    }

    private function cleanText(string $value, int $maxLength): string
    {
        return Str::limit(trim(strip_tags($value)), $maxLength, '');
    }

    private function sanitizeHtml(string $value): string
    {
        return strip_tags($value, '<b><strong><i><em><u><a><br><p><ul><ol><li><h2><h3><h4><blockquote>');
    }
}