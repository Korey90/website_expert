<?php

namespace Tests\Feature\AI;

use App\Services\AI\AiUnavailable;
use App\Services\AI\PublicAiBotGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicAiBotGuardTest extends TestCase
{
    public function test_missing_recaptcha_configuration_is_rejected(): void
    {
        config()->set('ai_assistant.recaptcha_required', true);
        config()->set('services.recaptcha.secret_key', null);

        $this->expectException(AiUnavailable::class);

        app(PublicAiBotGuard::class)->verify(
            Request::create('https://website-expert.uk/ai/message', 'POST', ['recaptcha_token' => 'example']),
            'website_ai_chat',
        );
    }

    public function test_successful_recaptcha_requires_matching_action_and_hostname(): void
    {
        config()->set('ai_assistant.recaptcha_required', true);
        config()->set('services.recaptcha.secret_key', 'test-secret');

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'website_ai_chat',
                'hostname' => 'website-expert.uk',
            ], 200),
        ]);

        app(PublicAiBotGuard::class)->verify(
            Request::create('https://website-expert.uk/ai/message', 'POST', ['recaptcha_token' => 'test-token']),
            'website_ai_chat',
        );

        Http::assertSentCount(1);
    }

    public function test_recaptcha_rejects_wrong_action_even_with_high_score(): void
    {
        config()->set('ai_assistant.recaptcha_required', true);
        config()->set('services.recaptcha.secret_key', 'test-secret');

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.99,
                'action' => 'some_other_form',
                'hostname' => 'website-expert.uk',
            ], 200),
        ]);

        $this->expectException(AiUnavailable::class);

        app(PublicAiBotGuard::class)->verify(
            Request::create('https://website-expert.uk/ai/message', 'POST', ['recaptcha_token' => 'test-token']),
            'website_ai_chat',
        );
    }
}
