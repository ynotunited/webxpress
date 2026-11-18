<?php

namespace Tests\Feature\Public;

use App\Models\ClientReview;
use App\Services\ProjectCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Client voices on the homepage.
 *
 * Two things are easy to break here and invisible in review.
 *
 * The section used to be an infinite marquee built by rendering every review
 * twice, so the markup grew at two cards per published row and a quote drifted
 * out of view while being read. It is paged now: a fixed set per page with
 * explicit controls. That only holds if the cards are no longer duplicated and
 * the controls reflect how many voices there actually are.
 *
 * And the company under a quote is the section's whole credibility. It used to
 * be free text, which drifted into crediting organisations that appear nowhere
 * in the portfolio. It is now read from the work catalogue through
 * `project_slug`, so a voice is traceable to published work. These tests pin
 * that, including the fallback for a review whose project has been retired.
 */
class ClientVoicesTest extends TestCase
{
    use RefreshDatabase;

    private function review(array $overrides = []): ClientReview
    {
        return ClientReview::create([
            'client_name' => 'Ngozi Adeleke',
            'client_role' => 'COO',
            'project_slug' => 'ecommerce-platform',
            'quote' => 'They understood the business before writing code.',
            'rating' => 5,
            'sort_order' => 1,
            'is_published' => true,
            ...$overrides,
        ]);
    }

    public function test_the_company_shown_is_the_one_the_work_index_lists(): void
    {
        $catalogueName = ProjectCatalog::get('ecommerce-platform')['name'];

        $review = $this->review();

        $this->assertSame($catalogueName, $review->creditedCompany());

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString($catalogueName, $html);
    }

    public function test_a_credited_company_links_to_the_work_it_describes(): void
    {
        $this->review();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(
            e(route('work.show', 'ecommerce-platform')),
            $html,
            'The company under a quote must link through to the published work.'
        );
    }

    /**
     * The catalogue is a PHP array, so nothing stops an admin saving a slug
     * that no longer resolves. That must degrade to plain text, not a dead link
     * and not a 500 from the section.
     */
    public function test_a_review_whose_project_is_gone_falls_back_to_the_stored_company(): void
    {
        $review = $this->review([
            'project_slug' => 'a-project-that-was-removed',
            'company' => 'A Former Client',
        ]);

        $this->assertNull($review->workProject());
        $this->assertSame('A Former Client', $review->creditedCompany());

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('A Former Client', $html);
        $this->assertStringNotContainsString(
            route('work.show', 'a-project-that-was-removed'),
            $html,
            'A retired slug must not produce a link to a project that does not exist.'
        );
    }

    public function test_each_quote_is_rendered_once(): void
    {
        $this->review(['client_name' => 'One', 'sort_order' => 1]);
        $this->review(['client_name' => 'Two', 'sort_order' => 2]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(
            2,
            substr_count($html, 'wx-testimonial-card'),
            'The marquee rendered every review twice to fake a loop. Cards must not be duplicated.'
        );
    }

    public function test_more_than_four_voices_produce_back_and_forward_controls(): void
    {
        for ($n = 1; $n <= 5; $n++) {
            $this->review(['client_name' => "Voice {$n}", 'sort_order' => $n]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-voices-prev', $html);
        $this->assertStringContainsString('data-voices-next', $html);
        $this->assertMatchesRegularExpression(
            '/data-voice-card\s+hidden/',
            $html,
            'Only the first four client voices should be visible before the visitor uses the pager.'
        );

        // Five voices at four to a set is two sets, so two dots. Matched on the
        // exact class because the list wrapping them is "wx-voices-dots".
        $this->assertSame(2, substr_count($html, 'class="wx-voices-dot"'));

        // The set boundaries have to be announced, or paging is silent.
        $this->assertStringContainsString('aria-live="polite"', $html);

        $script = (string) file_get_contents(resource_path('js/testimonials.js'));
        $this->assertStringContainsString(
            "document.addEventListener('DOMContentLoaded'",
            $script,
            'The pager must initialize without relying on Alpine expression evaluation.'
        );
        $this->assertStringNotContainsString(
            'clientVoices(',
            $script,
            'The pager must not rely on an Alpine component factory.'
        );
    }

    public function test_four_or_fewer_voices_hide_the_controls(): void
    {
        for ($n = 1; $n <= 4; $n++) {
            $this->review(['client_name' => "Voice {$n}", 'sort_order' => $n]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-voices-controls', $html);

        // No dots, though: there is only one set to move between.
        $this->assertStringNotContainsString('class="wx-voices-dot"', $html);
    }

    /**
     * Every card is in the HTML and the pager only decides which are on the
     * current set, so the quotes survive without JavaScript. Hiding them at the
     * server would leave the section reading as empty on a slow connection.
     */
    public function test_the_quotes_are_in_the_markup_without_javascript(): void
    {
        $this->review(['client_name' => 'Readable', 'quote' => 'A quote a crawler and a no-JS visitor both get.']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('A quote a crawler and a no-JS visitor both get.', $html);
    }

    public function test_unpublished_voices_are_not_shown(): void
    {
        $this->review(['client_name' => 'Published', 'sort_order' => 1]);
        $this->review([
            'client_name' => 'Withheld',
            'sort_order' => 2,
            'is_published' => false,
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Published', $html);
        $this->assertStringNotContainsString('Withheld', $html);
    }
}
