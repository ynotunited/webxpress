<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Services\ServiceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Every graph on the site, pulled out of the rendered HTML.
     */
    private function graphFor(string $uri): ?array
    {
        $response = $this->get($uri);
        $response->assertOk();

        $html = $response->getContent();

        preg_match(
            '~<script type="application/ld\+json"[^>]*>(.*?)</script>~s',
            $html,
            $match
        );

        $this->assertNotEmpty($match, "No JSON-LD block rendered at {$uri}");

        $graph = json_decode($match[1], true);

        $this->assertIsArray($graph, "JSON-LD at {$uri} is not valid JSON");
        $this->assertSame('https://schema.org', $graph['@context'] ?? null);

        return $graph;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function nodesOfType(?array $graph, string $type): array
    {
        return array_values(array_filter(
            $graph['@graph'] ?? [],
            fn (array $node): bool => ($node['@type'] ?? null) === $type
        ));
    }

    private function firstNodeOfType(array $graph, string $type): array
    {
        $nodes = $this->nodesOfType($graph, $type);

        $this->assertNotEmpty($nodes, "Expected a {$type} node");

        return $nodes[0];
    }

    public function test_home_page_states_the_organization(): void
    {
        SiteSetting::updateOrCreate(['key' => 'site_name'], ['value' => 'Webxpress Technologies']);
        SiteSetting::updateOrCreate(['key' => 'contact_email'], ['value' => 'hello@webxpresstech.com.ng']);
        SiteSetting::updateOrCreate(['key' => 'contact_phone'], ['value' => '+234 800 000 0000']);
        SiteSetting::updateOrCreate(['key' => 'linkedin_url'], ['value' => 'https://www.linkedin.com']);

        $graph = $this->graphFor('/');

        $organization = $this->firstNodeOfType($graph, 'Organization');

        $this->assertSame('Webxpress Technologies', $organization['name']);
        $this->assertSame(url('/'), $organization['url']);
        $this->assertSame(url('/#organization'), $organization['@id']);
        $this->assertArrayHasKey('logo', $organization);
        $this->assertSame(
            'https://www.linkedin.com',
            $organization['sameAs'][0],
        );
        $this->assertSame(
            'hello@webxpresstech.com.ng',
            $organization['contactPoint']['email'],
        );
        $this->assertContains('Nigeria', $organization['contactPoint']['areaServed']);

        $website = $this->firstNodeOfType($graph, 'WebSite');

        $this->assertSame(['@id' => url('/#organization')], $website['publisher']);
    }

    public function test_service_page_states_the_service_with_an_offer_catalog(): void
    {
        $slug = array_key_first(ServiceCatalog::all());

        $graph = $this->graphFor("/services/{$slug}");

        $service = $this->firstNodeOfType($graph, 'Service');

        $this->assertSame(url("/services/{$slug}").'#service', $service['@id']);
        $this->assertSame(['@id' => url('/#organization')], $service['provider']);
        $this->assertArrayHasKey('description', $service);
        $this->assertArrayHasKey('areaServed', $service);

        // The catalog is what turns "what's included" from a paragraph into data.
        $this->assertSame('OfferCatalog', $service['hasOfferCatalog']['@type']);
        $this->assertNotEmpty($service['hasOfferCatalog']['itemListElement']);
    }

    public function test_service_page_publishes_its_faq_as_paired_questions(): void
    {
        $slug = 'web-development';
        $service = ServiceCatalog::get($slug);

        $this->assertNotEmpty($service['faq'], 'Fixture service has no FAQ to publish');

        $graph = $this->graphFor("/services/{$slug}");

        $faq = $this->firstNodeOfType($graph, 'FAQPage');

        $this->assertCount(count($service['faq']), $faq['mainEntity']);

        foreach ($faq['mainEntity'] as $pair) {
            $this->assertSame('Question', $pair['@type']);
            $this->assertNotSame('', trim($pair['name']));
            $this->assertSame('Answer', $pair['acceptedAnswer']['@type']);
            $this->assertNotSame('', trim($pair['acceptedAnswer']['text']));
        }
    }

    public function test_service_page_carries_a_breadcrumb_trail(): void
    {
        $slug = array_key_first(ServiceCatalog::all());

        $graph = $this->graphFor("/services/{$slug}");

        $breadcrumb = $this->firstNodeOfType($graph, 'BreadcrumbList');

        $expected = ['Home', 'Services', ServiceCatalog::get($slug)['name']];

        $this->assertSame(
            $expected,
            array_column($breadcrumb['itemListElement'], 'name'),
        );
        $this->assertSame(
            [url('/'), url('/services'), url("/services/{$slug}")],
            array_column($breadcrumb['itemListElement'], 'item'),
        );
        $this->assertSame(
            [1, 2, 3],
            array_column($breadcrumb['itemListElement'], 'position'),
        );
    }

    public function test_service_page_faq_follows_a_cms_edit(): void
    {
        Page::updateOrCreate(
            ['slug' => 'branding'],
            [
                'title' => 'Branding',
                'is_published' => true,
                'structured_content' => [
                    'faq' => [
                        ['question' => 'How long does a rebrand take?', 'answer' => 'Usually eight weeks.'],
                    ],
                ],
            ],
        );

        $graph = $this->graphFor('/services/branding');

        $faq = $this->firstNodeOfType($graph, 'FAQPage');

        // One edited pair in, one pair out. If this drifted from the page the
        // markup would answer a question the page no longer asks.
        $this->assertCount(1, $faq['mainEntity']);
        $this->assertSame('How long does a rebrand take?', $faq['mainEntity'][0]['name']);
        $this->assertSame('Usually eight weeks.', $faq['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_insight_article_is_published_as_a_blog_posting(): void
    {
        $category = Category::firstOrCreate(['name' => 'Engineering'], ['slug' => 'engineering']);

        $post = Post::create([
            'category_id' => $category->id,
            'title' => 'Shipping a Laravel portal on a deadline',
            'slug' => 'shipping-a-laravel-portal',
            'excerpt' => 'What fourteen weeks of portal work actually involved.',
            'body' => '<p>Long form engineering write-up.</p>',
            'cover_image' => 'images/insights/portal.jpg',
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $graph = $this->graphFor('/insights/shipping-a-laravel-portal');

        $article = $this->firstNodeOfType($graph, 'BlogPosting');

        $this->assertSame($post->title, $article['headline']);
        $this->assertSame(
            ['@type' => 'WebPage', '@id' => url('/insights/shipping-a-laravel-portal')],
            $article['mainEntityOfPage'],
        );
        $this->assertSame('Engineering', $article['articleSection']);
        $this->assertNotSame('', $article['datePublished']);
        $this->assertNotEmpty($article['image']);
        $this->assertStringStartsWith('https://', $article['image']);
    }

    public function test_unpublished_article_gets_no_schema(): void
    {
        $post = Post::create([
            'title' => 'Draft insight',
            'slug' => 'draft-insight',
            'body' => '<p>Not finished.</p>',
            'is_published' => false,
        ]);

        $this->assertFalse($post->is_published);

        $response = $this->get('/insights/draft-insight');

        // No graph is better than a graph describing something a visitor cannot
        // read. The site-level organization is still emitted.
        if ($response->getStatusCode() === 200) {
            preg_match(
                '~<script type="application/ld\+json"[^>]*>(.*?)</script>~s',
                $response->getContent(),
                $match,
            );

            $this->assertIsArray($graph = json_decode($match[1] ?? '', true));
            $this->assertSame([], $this->nodesOfType($graph, 'BlogPosting'));
        } else {
            $response->assertNotFound();
        }
    }

    public function test_case_study_states_its_duration_as_iso_8601(): void
    {
        $slug = 'ecommerce-platform';

        $graph = $this->graphFor("/work/{$slug}");

        $project = $this->firstNodeOfType($graph, 'CreativeWork');

        // "14 weeks" in the copy becomes P14W in the markup, so a machine can
        // compare projects instead of parsing prose.
        $this->assertSame('P14W', $project['duration']);
        $this->assertSame(['@id' => url('/#organization')], $project['creator']);
    }

    public function test_services_index_lists_every_service(): void
    {
        $expected = count(ServiceCatalog::all());

        $graph = $this->graphFor('/services');

        $list = $this->firstNodeOfType($graph, 'ItemList');

        $this->assertCount($expected, $list['itemListElement']);

        foreach ($list['itemListElement'] as $position => $item) {
            $this->assertSame($position + 1, $item['position']);
            $this->assertSame('Service', $item['item']['@type']);
            $this->assertArrayHasKey('url', $item['item']);
        }
    }

    public function test_no_price_is_invented_anywhere_in_the_graph(): void
    {
        foreach (['/', '/services', '/services/web-development', '/insights'] as $uri) {
            $response = $this->get($uri);

            preg_match(
                '~<script type="application/ld\+json"[^>]*>(.*?)</script>~s',
                $response->getContent(),
                $match,
            );

            if ($match === []) {
                continue;
            }

            // The site quotes per project. A fabricated figure here would be a
            // claim the page never makes.
            $this->assertStringNotContainsString('"price"', $match[1], "A price was emitted at {$uri}");
            $this->assertStringNotContainsString('"priceSpecification"', $match[1]);
        }
    }

    public function test_editor_html_in_content_cannot_break_out_of_the_script_tag(): void
    {
        Page::updateOrCreate(
            ['slug' => 'web-development'],
            [
                'title' => 'Web Development',
                'is_published' => true,
                'structured_content' => [
                    'deliverables' => [
                        [
                            'title' => '</script><script>alert(1)</script>',
                            'description' => 'Attempted breakout.',
                        ],
                    ],
                ],
            ],
        );

        $response = $this->get('/services/web-development');

        preg_match(
            '~<script type="application/ld\+json"[^>]*>(.*?)</script>~s',
            $response->getContent(),
            $match,
        );

        $this->assertNotEmpty($match, 'The JSON-LD block was closed early by injected markup');
        $this->assertIsArray(json_decode($match[1], true));

        // The angle brackets must arrive escaped, so the parser above saw a
        // whole block rather than a truncated one.
        $this->assertStringNotContainsString('<script>', $match[1]);
    }
}