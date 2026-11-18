<?php

namespace Tests\Feature\Public;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The industries index.
 *
 * This page had no industries data model, so it was a lone <h1> on an empty
 * body while the footer linked to it. It is now driven by a card grid block
 * on the `industries` page record, which means both of its states need to
 * hold up: no cards (a finished page that tells the editor what to do) and
 * cards (the finished page).
 */
class IndustriesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_a_hero_from_the_page_record(): void
    {
        Page::create([
            'slug' => 'industries',
            'title' => 'Industries',
            'eyebrow' => 'Sectors',
            'headline' => 'Sectors we know well.',
            'intro' => 'Tell us the sector you work in.',
            'is_published' => true,
        ]);

        $body = $this->get('/industries')->assertOk()->getContent();

        $this->assertStringContainsString('Sectors we know well.', $body);
        $this->assertStringContainsString('Tell us the sector you work in.', $body);
    }

    public function test_it_falls_back_to_the_templates_own_copy_with_no_page_record(): void
    {
        // The page must not be blank on a database that has not been seeded
        // yet — the footer links to it either way.
        $body = $this->get('/industries')->assertOk()->getContent();

        $this->assertStringContainsString('Sectors we know well.', $body);
    }

    public function test_it_renders_the_cards_from_a_card_grid_block(): void
    {
        $page = Page::create([
            'slug' => 'industries',
            'title' => 'Industries',
            'headline' => 'Sectors we know well.',
            'is_published' => true,
        ]);

        PageSection::create([
            'page_id' => $page->id,
            'type' => 'card_grid',
            'key' => 'industry-cards',
            'sort_order' => 0,
            'data' => [
                'items' => [
                    ['name' => 'Education', 'description' => 'Enrolment and fee systems.'],
                    ['name' => 'Healthcare', 'description' => 'Records and patient portals.'],
                ],
            ],
        ]);

        $body = $this->get('/industries')->assertOk()->getContent();

        $this->assertStringContainsString('Education', $body);
        $this->assertStringContainsString('Enrolment and fee systems.', $body);
        $this->assertStringContainsString('Healthcare', $body);
        $this->assertStringContainsString('Records and patient portals.', $body);

        // The editor guidance only belongs on the empty state.
        $this->assertStringNotContainsString('No sectors listed yet', $body);
    }

    public function test_it_skips_a_card_with_no_title(): void
    {
        $page = Page::create([
            'slug' => 'industries',
            'title' => 'Industries',
            'headline' => 'Sectors we know well.',
            'is_published' => true,
        ]);

        PageSection::create([
            'page_id' => $page->id,
            'type' => 'card_grid',
            'key' => 'industry-cards',
            'sort_order' => 0,
            'data' => ['items' => [
                ['name' => 'Education', 'description' => 'Enrolment and fee systems.'],
                ['name' => '', 'description' => 'An editor has not finished this one.'],
            ]],
        ]);

        $body = $this->get('/industries')->assertOk()->getContent();

        $this->assertStringContainsString('Education', $body);
        $this->assertStringNotContainsString('An editor has not finished this one.', $body);
    }

    public function test_it_escapes_card_copy(): void
    {
        $page = Page::create([
            'slug' => 'industries',
            'title' => 'Industries',
            'headline' => 'Sectors we know well.',
            'is_published' => true,
        ]);

        PageSection::create([
            'page_id' => $page->id,
            'type' => 'card_grid',
            'key' => 'industry-cards',
            'sort_order' => 0,
            'data' => ['items' => [
                ['name' => '<script>alert("xss-card")</script>', 'description' => 'Safe.'],
            ]],
        ]);

        $body = $this->get('/industries')->assertOk()->getContent();

        $this->assertStringNotContainsString('alert("xss-card")', $body);
    }

    public function test_an_industry_detail_slug_still_404s(): void
    {
        // There is no industries data model, so a detail page has nothing to
        // render. It must not answer 200 with an empty body.
        $this->get('/industries/education')->assertNotFound();
    }
}
