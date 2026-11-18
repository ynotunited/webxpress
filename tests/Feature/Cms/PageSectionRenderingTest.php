<?php

namespace Tests\Feature\Cms;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * The homepage "Who We Are" block is the reference implementation of the
 * two-tier CMS contract: Blade keeps the design shell, page_sections keeps
 * the copy. These tests pin that contract down.
 */
class PageSectionRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function homePage(): Page
    {
        return Page::create([
            'slug' => 'home',
            'title' => 'Home',
            'is_published' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function bioData(): array
    {
        return [
            'label' => 'Who We Are',
            'quote' => 'A distinctive pull quote.',
            'paragraphs' => [
                'First database-driven paragraph.',
                'Second database-driven paragraph.',
            ],
            'cta' => ['label' => 'Our Story', 'route' => 'about'],
            'stats' => [
                ['variant' => 'plain', 'value' => '15+', 'label' => 'Years of Experience'],
                ['variant' => 'inverted', 'value' => '200+', 'label' => 'Projects Delivered'],
                ['variant' => 'dashed', 'prefix' => 'Since', 'value' => '2010'],
            ],
        ];
    }

    private function bioBlock(array $data = []): PageSection
    {
        return PageSection::create([
            'page_id' => $this->homePage()->id,
            'type' => PageSection::TYPE_BIO,
            'key' => 'who-we-are',
            'sort_order' => 30,
            'data' => $data ?: $this->bioData(),
        ]);
    }

    public function test_homepage_renders_the_bio_block_from_the_database(): void
    {
        $this->bioBlock();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('A distinctive pull quote.');
        $response->assertSee('First database-driven paragraph.');
        $response->assertSee('Second database-driven paragraph.');
        $response->assertSee('Who We Are');
        $response->assertSee('Our Story');
    }

    public function test_homepage_renders_every_stat_from_the_database(): void
    {
        $this->bioBlock();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('15+');
        $response->assertSee('Years of Experience');
        $response->assertSee('200+');
        $response->assertSee('Projects Delivered');
        $response->assertSee('Since');
        $response->assertSee('2010');
    }

    public function test_editing_a_block_changes_the_public_page(): void
    {
        $block = $this->bioBlock();

        $data = $block->data;
        $data['quote'] = 'An edited quote from the admin.';
        $data['stats'][0]['value'] = '16+';
        $block->update(['data' => $data]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('An edited quote from the admin.');
        $response->assertSee('16+');
        $response->assertDontSee('A distinctive pull quote.');
        $response->assertDontSee('15+');
    }

    public function test_block_content_is_escaped(): void
    {
        $data = $this->bioData();
        $data['quote'] = '<script>alert(1)</script>';
        $this->bioBlock($data);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_unknown_block_type_falls_back_to_the_blade_shell(): void
    {
        $block = $this->bioBlock();
        $block->update(['type' => 'no-such-block-type']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Technology should solve real business problems', false);
    }

    public function test_missing_block_row_falls_back_to_the_blade_shell(): void
    {
        $this->homePage();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Technology should solve real business problems', false);
    }

    public function test_design_shell_classes_survive_the_promotion(): void
    {
        $this->bioBlock();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('section-padding', false);
        $response->assertSee('container-wx', false);
        $response->assertSee('section-label', false);
        $response->assertSee('accent-bar', false);
        $response->assertSee('label-sm', false);
        $response->assertSee('btn btn-secondary', false);
        $response->assertSee('animate-on-scroll', false);
    }

    public function test_page_has_exactly_one_head_and_no_scraped_boilerplate(): void
    {
        $this->bioBlock();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<head>'));
        $this->assertSame(1, substr_count($html, '<title>'));
        $this->assertSame(1, substr_count($html, '<meta name="csrf-token"'));
        $this->assertSame(1, substr_count($html, 'name="twitter:card"'));
    }

    public function test_cta_resolves_a_named_route_and_hides_when_unset(): void
    {
        $this->bioBlock();

        $this->get('/')->assertOk()->assertSee(url('/about'), false);

        $block = PageSection::where('key', 'who-we-are')->first();
        $data = $block->data;
        $data['cta'] = ['label' => 'Our Story', 'route' => 'no-such-route'];
        $block->update(['data' => $data]);

        $this->get('/')->assertOk()->assertDontSee('>Our Story<', false);
    }

    public function test_seeder_promotes_the_block_and_is_idempotent(): void
    {
        $this->homePage();

        $this->artisan('db:seed', ['--class' => 'PageSectionSeeder'])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => 'PageSectionSeeder'])->assertSuccessful();

        $this->assertSame(1, PageSection::where('key', 'who-we-are')->count());

        $this->get('/')->assertOk()->assertSee('Technology should solve real business problems', false);
    }

    public function test_section_relationships_are_ordered(): void
    {
        $page = $this->homePage();

        PageSection::create(['page_id' => $page->id, 'type' => 'bio', 'key' => 'third', 'sort_order' => 30, 'data' => []]);
        PageSection::create(['page_id' => $page->id, 'type' => 'bio', 'key' => 'first', 'sort_order' => 10, 'data' => []]);
        PageSection::create(['page_id' => $page->id, 'type' => 'bio', 'key' => 'second', 'sort_order' => 20, 'data' => []]);

        $keys = $page->sections()->pluck('key')->all();

        $this->assertSame(['first', 'second', 'third'], $keys);
    }

    public function test_page_section_key_is_unique_per_page(): void
    {
        $page = $this->homePage();
        PageSection::create(['page_id' => $page->id, 'type' => 'bio', 'key' => 'who-we-are', 'sort_order' => 1, 'data' => []]);

        // Through the model this is a field-level error the admin can act on,
        // rather than an unhandled database exception.
        $this->expectException(ValidationException::class);

        PageSection::create(['page_id' => $page->id, 'type' => 'bio', 'key' => 'who-we-are', 'sort_order' => 2, 'data' => []]);
    }

    public function test_the_database_also_rejects_a_duplicate_block_key(): void
    {
        $page = $this->homePage();
        PageSection::create(['page_id' => $page->id, 'type' => 'bio', 'key' => 'who-we-are', 'sort_order' => 1, 'data' => []]);

        // The unique index is the backstop for writes that bypass the model.
        $this->expectException(QueryException::class);

        DB::table('page_sections')->insert([
            'page_id' => $page->id,
            'type' => 'bio',
            'key' => 'who-we-are',
            'sort_order' => 2,
            'data' => '{}',
        ]);
    }
}
