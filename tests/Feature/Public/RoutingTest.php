<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public routing behaviour.
 *
 * Detail routes that never look anything up return 200 for every slug, which
 * lets search engines index an unbounded set of thin duplicate pages. Each
 * unknown slug must resolve to a real 404.
 */
class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public static function publicPageProvider(): array
    {
        return [
            'home' => ['/', 200],
            'about' => ['/about', 200],
            'services' => ['/services', 200],
            'work' => ['/work', 200],
            'industries' => ['/industries', 200],
            'insights' => ['/insights', 200],
            'contact' => ['/contact', 200],
        ];
    }

    /**
     * @dataProvider publicPageProvider
     */
    public function test_core_pages_respond(string $uri, int $expected): void
    {
        $this->get($uri)->assertStatus($expected);
    }

    public function test_every_page_renders_exactly_one_head(): void
    {
        foreach (['/', '/about', '/services', '/work', '/insights', '/industries', '/contact'] as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, '<head>'), "multiple <head> on {$uri}");
            $this->assertSame(1, substr_count($html, '<title>'), "multiple <title> on {$uri}");
            // Count the tag, not the JS querySelector that reads it.
            $this->assertSame(1, substr_count($html, '<meta name="csrf-token"'), "multiple csrf meta on {$uri}");
        }
    }

    public function test_public_pages_have_exactly_one_charset_meta(): void
    {
        foreach (['/', '/about', '/services', '/work', '/insights', '/industries', '/contact'] as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();

            // Scraped page bodies used to carry their own <head> and charset
            // meta, which produced a second one inside the layout.
            $this->assertSame(1, substr_count($html, '<meta charset'), "stray charset meta on {$uri}");
        }
    }

    public function test_unknown_service_slug_returns_404(): void
    {
        $this->get('/services/no-such-service')->assertNotFound();
    }

    public function test_known_service_slug_returns_200(): void
    {
        $this->get('/services/web-development')->assertOk();
    }

    public function test_unknown_work_slug_returns_404(): void
    {
        $this->get('/work/no-such-project')->assertNotFound();
    }

    public function test_unknown_industry_slug_returns_404(): void
    {
        $this->get('/industries/no-such-industry')->assertNotFound();
    }

    public function test_unknown_insight_slug_returns_404(): void
    {
        $this->get('/insights/no-such-article')->assertNotFound();
    }

    public function test_published_insight_renders_its_own_content(): void
    {
        $category = Category::create(['name' => 'Technology', 'slug' => 'technology']);
        Post::create([
            'category_id' => $category->id,
            'title' => 'A Distinctive Article Title',
            'slug' => 'a-distinctive-article',
            'excerpt' => 'A short summary.',
            'body' => '<p>Distinctive body copy for testing.</p>',
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $response = $this->get('/insights/a-distinctive-article');

        $response->assertOk();
        $response->assertSee('A Distinctive Article Title');
        $response->assertSee('Distinctive body copy for testing.');
        $response->assertSee('Technology');
    }

    public function test_unpublished_insight_returns_404(): void
    {
        $category = Category::create(['name' => 'Technology', 'slug' => 'technology']);
        Post::create([
            'category_id' => $category->id,
            'title' => 'Draft',
            'slug' => 'a-draft',
            'body' => '<p>Hidden.</p>',
            'published_at' => now()->subDay(),
            'is_published' => false,
        ]);

        $this->get('/insights/a-draft')->assertNotFound();
    }

    public function test_future_dated_insight_returns_404(): void
    {
        $category = Category::create(['name' => 'Technology', 'slug' => 'technology']);
        Post::create([
            'category_id' => $category->id,
            'title' => 'Scheduled',
            'slug' => 'a-scheduled-post',
            'body' => '<p>Later.</p>',
            'published_at' => now()->addWeek(),
            'is_published' => true,
        ]);

        $this->get('/insights/a-scheduled-post')->assertNotFound();
    }

    public function test_insights_index_lists_published_posts_only(): void
    {
        $category = Category::create(['name' => 'Technology', 'slug' => 'technology']);

        Post::create([
            'category_id' => $category->id, 'title' => 'Live Article', 'slug' => 'live',
            'body' => '<p>Live.</p>', 'published_at' => now()->subDay(), 'is_published' => true,
        ]);
        Post::create([
            'category_id' => $category->id, 'title' => 'Hidden Draft', 'slug' => 'hidden',
            'body' => '<p>Hidden.</p>', 'published_at' => now()->subDay(), 'is_published' => false,
        ]);

        $html = $this->get('/insights')->assertOk()->getContent();

        $this->assertStringContainsString('Live Article', $html);
        $this->assertStringNotContainsString('Hidden Draft', $html);
    }
}
