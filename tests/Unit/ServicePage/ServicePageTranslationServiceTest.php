<?php

namespace Tests\Unit\ServicePage;

use App\Exceptions\LandingPageGenerationException;
use App\Services\LandingPage\OpenAiLandingClient;
use App\Services\ServicePage\ServicePageTranslationService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServicePageTranslationServiceTest extends TestCase
{
    private OpenAiLandingClient&MockInterface $client;
    private ServicePageTranslationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client  = Mockery::mock(OpenAiLandingClient::class);
        $this->service = new ServicePageTranslationService($this->client);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // translatePage()
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function translate_page_returns_en_and_pt_structures(): void
    {
        $this->client
            ->shouldReceive('generateStructuredLanding')
            ->once()
            ->andReturn([
                'content' => [
                    'en' => [
                        'title'            => 'SEO Services',
                        'meta_title'       => 'SEO Services | Agency',
                        'meta_description' => 'We provide SEO services.',
                        'nav_label'        => 'SEO',
                    ],
                    'pt' => [
                        'title'            => 'Serviços SEO',
                        'meta_title'       => 'Serviços SEO | Agência',
                        'meta_description' => 'Oferecemos serviços SEO.',
                        'nav_label'        => 'SEO',
                    ],
                ],
            ]);

        $result = $this->service->translatePage([
            'title'            => 'Usługi SEO',
            'meta_title'       => 'Usługi SEO | Agencja',
            'meta_description' => 'Świadczymy usługi SEO.',
            'nav_label'        => 'SEO',
        ]);

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('pt', $result);
        $this->assertSame('SEO Services',          $result['en']['title']);
        $this->assertSame('Serviços SEO',          $result['pt']['title']);
        $this->assertSame('SEO Services | Agency', $result['en']['meta_title']);
        $this->assertSame('SEO',                   $result['en']['nav_label']);
    }

    #[Test]
    public function translate_page_fills_all_four_keys_in_each_locale(): void
    {
        $this->client
            ->shouldReceive('generateStructuredLanding')
            ->once()
            ->andReturn([
                'content' => [
                    'en' => ['title' => 'T', 'meta_title' => 'MT', 'meta_description' => 'MD', 'nav_label' => 'NL'],
                    'pt' => ['title' => 'T', 'meta_title' => 'MT', 'meta_description' => 'MD', 'nav_label' => 'NL'],
                ],
            ]);

        $result = $this->service->translatePage([
            'title' => 'T', 'meta_title' => 'MT', 'meta_description' => 'MD', 'nav_label' => 'NL',
        ]);

        foreach (['en', 'pt'] as $locale) {
            $this->assertArrayHasKey('title',            $result[$locale]);
            $this->assertArrayHasKey('meta_title',       $result[$locale]);
            $this->assertArrayHasKey('meta_description', $result[$locale]);
            $this->assertArrayHasKey('nav_label',        $result[$locale]);
        }
    }

    #[Test]
    public function translate_page_throws_on_openai_error(): void
    {
        $this->client
            ->shouldReceive('generateStructuredLanding')
            ->once()
            ->andThrow(new LandingPageGenerationException('API error', 'openai_error', 500));

        $this->expectException(LandingPageGenerationException::class);

        $this->service->translatePage(['title' => 'Test', 'meta_title' => '', 'meta_description' => '', 'nav_label' => '']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // translateBlock()
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function translate_block_merges_en_and_pt_into_content(): void
    {
        $this->client
            ->shouldReceive('generateStructuredLanding')
            ->once()
            ->andReturn([
                'content' => [
                    'en' => ['heading' => 'SEO Services', 'subheading' => 'We rank #1'],
                    'pt' => ['heading' => 'Serviços SEO', 'subheading' => 'Estamos no #1'],
                ],
            ]);

        $content = [
            'heading_pl'    => 'Usługi SEO',
            'heading_en'    => '',
            'heading_pt'    => '',
            'subheading_pl' => 'Jesteśmy #1',
            'subheading_en' => '',
            'subheading_pt' => '',
        ];

        $result = $this->service->translateBlock('hero', $content);

        $this->assertSame('SEO Services',   $result['heading_en']);
        $this->assertSame('Serviços SEO',   $result['heading_pt']);
        $this->assertSame('We rank #1',     $result['subheading_en']);
        $this->assertSame('Estamos no #1',  $result['subheading_pt']);
        // PL originals must be preserved
        $this->assertSame('Usługi SEO',     $result['heading_pl']);
        $this->assertSame('Jesteśmy #1',    $result['subheading_pl']);
    }

    #[Test]
    public function translate_block_returns_content_unchanged_when_no_pl_fields(): void
    {
        $this->client->shouldNotReceive('generateStructuredLanding');

        $content = [
            'heading_en' => 'Already in English',
            'heading_pt' => 'Já em Português',
        ];

        $result = $this->service->translateBlock('hero', $content);

        $this->assertSame($content, $result);
    }

    #[Test]
    public function translate_block_handles_nested_items_array(): void
    {
        $this->client
            ->shouldReceive('generateStructuredLanding')
            ->once()
            ->andReturn([
                'content' => [
                    'en' => [
                        'heading' => 'FAQ',
                        'items'   => [
                            ['question' => 'Question 1?', 'answer' => 'Answer 1.'],
                        ],
                    ],
                    'pt' => [
                        'heading' => 'FAQ',
                        'items'   => [
                            ['question' => 'Pergunta 1?', 'answer' => 'Resposta 1.'],
                        ],
                    ],
                ],
            ]);

        $content = [
            'heading_pl' => 'FAQ',
            'heading_en' => '',
            'heading_pt' => '',
            'items'      => [
                [
                    'question_pl' => 'Pytanie 1?',
                    'question_en' => '',
                    'question_pt' => '',
                    'answer_pl'   => 'Odpowiedź 1.',
                    'answer_en'   => '',
                    'answer_pt'   => '',
                ],
            ],
        ];

        $result = $this->service->translateBlock('faq', $content);

        $this->assertSame('Question 1?',  $result['items'][0]['question_en']);
        $this->assertSame('Pergunta 1?',  $result['items'][0]['question_pt']);
        $this->assertSame('Answer 1.',    $result['items'][0]['answer_en']);
        $this->assertSame('Resposta 1.',  $result['items'][0]['answer_pt']);
        // PL preserved
        $this->assertSame('Pytanie 1?',   $result['items'][0]['question_pl']);
    }

    #[Test]
    public function translate_block_skips_empty_pl_strings(): void
    {
        // Only non-empty PL fields should be sent; empty ones should not prevent the call
        // but the logic in translateBlock sends only non-empty _pl values
        $this->client
            ->shouldReceive('generateStructuredLanding')
            ->once()
            ->andReturn([
                'content' => [
                    'en' => ['heading' => 'EN heading'],
                    'pt' => ['heading' => 'PT heading'],
                ],
            ]);

        $content = [
            'heading_pl'  => 'Nagłówek',
            'empty_pl'    => '',           // empty — not sent
            'existing_en' => 'keep me',    // non-pl field — passed through
        ];

        $result = $this->service->translateBlock('hero', $content);

        $this->assertSame('EN heading', $result['heading_en']);
        $this->assertSame('PT heading', $result['heading_pt']);
        $this->assertSame('keep me',    $result['existing_en']);
    }

    #[Test]
    public function translate_block_throws_on_openai_error(): void
    {
        $this->client
            ->shouldReceive('generateStructuredLanding')
            ->once()
            ->andThrow(new LandingPageGenerationException('Fail', 'error', 500));

        $this->expectException(LandingPageGenerationException::class);

        $this->service->translateBlock('hero', ['heading_pl' => 'Test']);
    }
}
