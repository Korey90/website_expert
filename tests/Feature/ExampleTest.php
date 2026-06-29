<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('homepageDescriptions')]
    public function test_homepage_returns_a_single_localized_meta_description(string $locale, string $expected): void
    {
        $response = $this
            ->withSession(['locale' => $locale])
            ->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('locale', $locale)
            ->where('seo.description', $expected));

        $document = new DOMDocument;
        $document->loadHTML($response->getContent());

        $descriptions = (new DOMXPath($document))->query('//meta[@name="description"]');

        $this->assertCount(1, $descriptions);

        $content = trim($descriptions->item(0)->getAttribute('content'));

        $this->assertNotSame('', $content);
        $this->assertSame($expected, $content);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function homepageDescriptions(): array
    {
        return [
            'Polish' => [
                'pl',
                'Profesjonalne projektowanie stron internetowych, e-commerce i usługi SEO w Belfaście oraz w całej Irlandii Północnej. Stała cena, realizacja w 2–6 tygodni. Bezpłatna wycena w ciągu 24 godzin — website-expert.uk',
            ],
            'English' => [
                'en',
                'Professional web design, e-commerce and SEO services in Belfast and across Northern Ireland. Fixed price, delivered in 2–6 weeks. Free quote in 24 hours — website-expert.uk',
            ],
            'Portuguese' => [
                'pt',
                'Serviços profissionais de web design, comércio eletrónico e SEO em Belfast e em toda a Irlanda do Norte. Preço fixo, entrega em 2–6 semanas. Orçamento gratuito em 24 horas — website-expert.uk',
            ],
        ];
    }
}
