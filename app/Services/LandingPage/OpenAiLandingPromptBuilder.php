<?php

namespace App\Services\LandingPage;

class OpenAiLandingPromptBuilder
{
    public function buildSystemPrompt(array $context): string
    {
        $allowedTypes = implode(', ', array_keys(config('landing_pages.section_types', [])));

        return <<<PROMPT
You are an expert SaaS conversion copywriter.
Generate a landing page draft as strict JSON only.
Do not return markdown.
Do not return prose outside JSON.
Do not invent unsupported section types.

Supported section types: {$allowedTypes}

Return an object with this shape:
{
  "title": "string",
  "slug_suggestion": "string",
  "language": "en|pl|pt",
  "template_key": "services|lead_magnet|portfolio|null",
  "meta": {
    "meta_title": "string",
    "meta_description": "string",
    "conversion_goal": "book_call|download|purchase|contact"
  },
  "sections": [
    {
      "type": "hero|features|testimonials|cta|form|faq|text|video",
      "content": {},
      "settings": {
        "background": "white|dark|primary|gradient",
        "padding": "sm|md|lg",
        "visible": true
      }
    }
  ]
}

Rules:
- Always include a hero section and a form section.
- Use #form for CTA links when the page goal is lead capture.
- Keep content concrete and business-specific.
- Do not fabricate client names or fake testimonials if there is no evidence. Prefer features or FAQ instead.
- Use plain text values in JSON fields. Avoid long HTML. Only text sections may contain simple HTML.

Business context:
{$this->toPrettyJson($context)}
PROMPT;
    }

    public function buildUserPrompt(array $input): string
    {
        return <<<PROMPT
Generate one landing page draft using the following request:
{$this->toPrettyJson($input)}

Focus on a clear conversion flow, strong hero, concrete benefits, one mid-page CTA, and a practical lead form.
PROMPT;
    }

    public function buildSectionRegenerationPrompt(array $context, string $sectionType, array $existingSections, array $input): string
    {
        return <<<PROMPT
Regenerate exactly one landing page section as strict JSON only.

Return this shape:
{
  "section": {
    "type": "{$sectionType}",
    "content": {},
    "settings": {
      "background": "white|dark|primary|gradient",
      "padding": "sm|md|lg",
      "visible": true
    }
  }
}

Keep all content aligned with this business context:
{$this->toPrettyJson($context)}

Current sections:
{$this->toPrettyJson($existingSections)}

Regeneration request:
{$this->toPrettyJson($input)}
PROMPT;
    }

    private function toPrettyJson(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}