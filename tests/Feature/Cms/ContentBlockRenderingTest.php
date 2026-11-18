<?php

namespace Tests\Feature\Cms;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The database-driven block types.
 *
 * These five exist so a page can be assembled from content in the admin
 * without a bespoke Blade template: a heading, a rich text section, an image
 * beside copy, a set of figures, and an FAQ. Each is rendered by
 * resources/views/sections/blocks/<type>.blade.php.
 *
 * The properties that matter, and that a content editor cannot verify by
 * looking: a block with no content renders nothing rather than an empty shell;
 * text is escaped; rich text is sanitised; and a block never invents a URL it
 * was not given.
 */
class ContentBlockRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_section_heading_renders_its_copy(): void
    {
        $this->renderPage('section_head', [
            'label' => 'Capabilities',
            'heading' => 'What we do',
            'subheading' => 'The things we are asked to build.',
        ]);

        $body = $this->get('/block-page')->assertOk()->getContent();

        $this->assertStringContainsString('What we do', $body);
        $this->assertStringContainsString('Capabilities', $body);
        $this->assertStringContainsString('The things we are asked to build.', $body);
    }

    public function test_a_prose_block_renders_its_rich_text(): void
    {
        $this->renderPage('prose', [
            'heading' => 'How we work',
            'body' => '<p>We ship in <strong>small batches</strong>.</p><ul><li>Weekly</li></ul>',
        ]);

        $body = $this->get('/block-page')->assertOk()->getContent();

        $this->assertStringContainsString('How we work', $body);
        $this->assertStringContainsString('<strong>small batches</strong>', $body);
        $this->assertStringContainsString('<li>Weekly</li>', $body);
    }

    public function test_a_prose_block_strips_script_from_its_body(): void
    {
        // `body` is the one field echoed unescaped, so it is the one field an
        // editor's paste buffer can turn into stored XSS. The assertions look
        // for the injected payload rather than for a <script> tag, because the
        // page's own layout legitimately contains script tags.
        $this->renderPage('prose', [
            'body' => '<p>Safe copy.</p><script>alert("xss-prose")</script><img src=x onerror="alert(2)">',
        ]);

        $body = $this->get('/block-page')->assertOk()->getContent();

        $this->assertStringNotContainsString('xss-prose', $body);
        $this->assertStringNotContainsString('onerror=', $body);
        $this->assertStringContainsString('Safe copy.', $body);
    }

    public function test_an_image_and_text_block_renders_its_bullets_and_link(): void
    {
        $this->renderPage('feature_split', [
            'heading' => 'Built to be maintained',
            'body' => 'The team that wrote it is the team that updates it.',
            'image' => '/storage/page-sections/studio.png',
            'image_alt' => 'The Webxpress studio',
            'image_position' => 'right',
            'bullets' => [
                ['text' => 'One named engineer'],
                ['text' => 'Documented handover'],
            ],
            'cta' => ['label' => 'Start a project', 'route' => 'contact'],
        ]);

        $body = $this->get('/block-page')->assertOk()->getContent();

        $this->assertStringContainsString('Built to be maintained', $body);
        $this->assertStringContainsString('One named engineer', $body);
        $this->assertStringContainsString('Documented handover', $body);
        $this->assertStringContainsString('Start a project', $body);
        $this->assertStringContainsString('/contact', $body);
        $this->assertStringContainsString('The Webxpress studio', $body);
    }

    public function test_a_stats_block_renders_every_figure(): void
    {
        $this->renderPage('stats', [
            'heading' => 'By the numbers',
            'items' => [
                ['value' => '2010', 'label' => 'Founded'],
                ['value' => '120+', 'label' => 'Projects shipped'],
            ],
        ]);

        $body = $this->get('/block-page')->assertOk()->getContent();

        $this->assertStringContainsString('By the numbers', $body);
        $this->assertStringContainsString('2010', $body);
        $this->assertStringContainsString('Founded', $body);
        $this->assertStringContainsString('120+', $body);
        $this->assertStringContainsString('Projects shipped', $body);
    }

    public function test_an_faq_block_renders_its_questions(): void
    {
        $this->renderPage('faq', [
            'heading' => 'Questions',
            'items' => [
                ['question' => 'How long does a build take?', 'answer' => '<p>Six to twelve weeks.</p>'],
                ['question' => 'Do you support handover?', 'answer' => '<p>Always.</p>'],
            ],
        ]);

        $body = $this->get('/block-page')->assertOk()->getContent();

        $this->assertStringContainsString('Questions', $body);
        $this->assertStringContainsString('How long does a build take?', $body);
        $this->assertStringContainsString('Six to twelve weeks.', $body);
        $this->assertStringContainsString('Do you support handover?', $body);
    }

    public function test_an_faq_answer_cannot_inject_script(): void
    {
        $this->renderPage('faq', [
            'heading' => 'Questions',
            'items' => [
                [
                    'question' => 'Safe question?',
                    'answer' => '<p>Safe answer.</p><script>alert("xss-faq")</script>',
                ],
            ],
        ]);

        $body = $this->get('/block-page')->assertOk()->getContent();

        $this->assertStringNotContainsString('xss-faq', $body);
        $this->assertStringContainsString('Safe answer.', $body);
    }

    /**
     * An empty block is the common state of a page that is still being built,
     * so it must not leave a heading, a rule or an empty grid on the page.
     */
    public function test_an_empty_block_renders_nothing(): void
    {
        foreach (['section_head', 'prose', 'feature_split', 'stats', 'faq'] as $type) {
            // Hyphens, not underscores: the catch-all slug pattern only accepts
            // lowercase alphanumerics and hyphens.
            $slug = 'empty-'.str_replace('_', '-', $type);

            $this->renderPage($type, [], $slug);

            $body = $this->get('/'.$slug)->assertOk()->getContent();

            $this->assertStringNotContainsString(
                'section-padding',
                $body,
                "an empty '{$type}' block left an empty section shell on the page"
            );
        }
    }

    public function test_a_block_with_an_unresolvable_cta_route_omits_the_button(): void
    {
        // The route is validated on save, so this is a hand-edited or
        // imported row. Rendering must not produce a link to nowhere.
        $this->renderPage('feature_split', [
            'heading' => 'Still renders',
            'cta' => ['label' => 'Go', 'route' => 'no-such-route'],
        ]);

        $body = $this->get('/block-page')->assertOk()->getContent();

        $this->assertStringContainsString('Still renders', $body);
        $this->assertStringNotContainsString('>Go<', $body);
    }

    public function test_a_block_does_not_emit_a_broken_image_when_there_is_no_file(): void
    {
        $this->renderPage('feature_split', [
            'heading' => 'No image',
            'image' => '../../.env',
        ]);

        $body = $this->get('/block-page')->assertOk()->getContent();

        $this->assertStringNotContainsString('.env', $body);
        $this->assertStringContainsString('No image', $body);
    }

    public function test_a_stats_block_hides_a_figure_with_no_value(): void
    {
        // Half-filled rows are normal while an editor is still collecting
        // figures; a bare label with no number is not a statistic.
        $this->renderPage('stats', [
            'heading' => 'By the numbers',
            'items' => [
                ['value' => '2010', 'label' => 'Founded'],
                ['value' => '', 'label' => 'Not yet known'],
            ],
        ]);

        $body = $this->get('/block-page')->assertOk()->getContent();

        $this->assertStringContainsString('Founded', $body);
        $this->assertStringNotContainsString('Not yet known', $body);
    }

    /**
     * Create a page whose only content is one block of the given type.
     */
    private function renderPage(string $type, array $data, ?string $slug = null): void
    {
        $slug ??= 'block-page';

        $page = Page::create([
            'slug' => $slug,
            'title' => 'Block Page',
            'is_published' => true,
        ]);

        PageSection::create([
            'page_id' => $page->id,
            'type' => $type,
            'key' => 'under-test',
            'sort_order' => 0,
            'data' => $data,
        ]);
    }
}
