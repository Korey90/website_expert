<?php

namespace Tests\Feature\Blog;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeArticle(array $overrides = []): Article
    {
        return Article::create(array_merge([
            'title' => ['en' => 'How much does a website cost in the UK?', 'pl' => 'Ile kosztuje strona internetowa w UK?'],
            'excerpt' => ['en' => 'A practical pricing guide.', 'pl' => 'Praktyczny przewodnik po cenach.'],
            'content' => ['en' => '<p>Content in English.</p>', 'pl' => '<p>Treść po polsku.</p>'],
            'meta_title' => ['en' => 'Website cost UK', 'pl' => 'Cena strony UK'],
            'meta_description' => ['en' => 'Meta EN', 'pl' => 'Meta PL'],
            'category' => 'pricing',
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));
    }

    public function test_index_returns_200(): void
    {
        $this->makeArticle();

        $this->get('/blog')->assertStatus(200);
    }

    public function test_index_lists_only_published_articles(): void
    {
        $published = $this->makeArticle(['title' => ['en' => 'Published One']]);
        $this->makeArticle(['title' => ['en' => 'Draft One'], 'status' => 'draft', 'slug' => 'draft-one']);

        $response = $this->get('/blog');

        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Index')
            ->has('articles.data', 1)
        );

        $this->assertSame('published-one', $published->fresh()->slug);
    }

    public function test_show_returns_200_for_published_article(): void
    {
        $article = $this->makeArticle();

        $this->get("/blog/{$article->slug}")->assertStatus(200);
    }

    public function test_show_returns_404_for_draft_article(): void
    {
        $article = $this->makeArticle(['status' => 'draft']);

        $this->get("/blog/{$article->slug}")->assertStatus(404);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->get('/blog/does-not-exist')->assertStatus(404);
    }

    public function test_show_resolves_polish_content_for_pl_locale(): void
    {
        $article = $this->makeArticle();

        $response = $this->withSession(['locale' => 'pl'])->get("/blog/{$article->slug}");

        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Show')
            ->where('article.title', 'Ile kosztuje strona internetowa w UK?')
        );
    }

    public function test_sitemap_contains_published_article(): void
    {
        $article = $this->makeArticle();

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString("/blog/{$article->slug}", $xml);
    }

    public function test_sitemap_excludes_draft_article(): void
    {
        $article = $this->makeArticle(['status' => 'draft']);

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString("/blog/{$article->slug}", $xml);
    }
}
