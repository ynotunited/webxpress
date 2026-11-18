<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Post;
use App\Services\ProjectCatalog;
use App\Services\ServiceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Keyboard and screen reader regression tests.
 *
 * Every failure in this file corresponds to a real defect that shipped:
 *
 *  - The focus ring was red-on-navy at 2.81:1, so on every dark section of the
 *    site the indicator was present in the DOM and invisible on screen.
 *  - The contact wizard deleted the control the user had just activated on
 *    every step, dropping focus to <body>.
 *  - "Load more" removed itself on the last page, also dropping focus.
 *  - Escape closed the mobile menu but never returned focus to its trigger.
 *  - Active navigation state and breadcrumb ends were conveyed by colour only.
 *  - Focusable marquee regions had no role, so their names were never read.
 *  - Chat bubbles were unlabelled, so the transcript was anonymous text.
 *
 * These are the failures that a visual review cannot catch and that a
 * regenerated stylesheet will not reintroduce, so they are pinned here.
 */
class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public static function publicPageProvider(): array
    {
        return [
            'home' => ['/'],
            'about' => ['/about'],
            'services' => ['/services'],
            'work' => ['/work'],
            'industries' => ['/industries'],
            'insights' => ['/insights'],
            'contact' => ['/contact'],
        ];
    }

    /* ── Focus indicator ───────────────────────────────────── */

    /**
     * @dataProvider publicPageProvider
     */
    public function test_every_public_page_has_a_skip_link_as_the_first_focusable_element(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringContainsString(
            'href="#main-content"',
            $html,
            "{$uri} has no skip link, so a keyboard user tabs through the entire nav on every page."
        );

        // The link must precede the first <nav>, otherwise it is not actually first.
        $this->assertLessThan(
            strpos($html, '<nav'),
            strpos($html, 'href="#main-content"'),
            "{$uri} has a skip link, but it appears after the navigation."
        );
    }

    /**
     * @dataProvider publicPageProvider
     */
    public function test_every_public_page_exposes_a_focusable_main_landmark(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringContainsString('id="main-content"', $html);
        // Without tabindex="-1" the skip link moves the viewport but not focus,
        // so the next Tab still starts from the top of the document.
        $this->assertMatchesRegularExpression(
            '/id="main-content"[^>]*tabindex="-1"/',
            $html,
            "{$uri} targets a main landmark that cannot receive programmatic focus."
        );
    }

    public function test_the_focus_ring_is_a_two_band_indicator_that_survives_both_surfaces(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString(':focus-visible', $css);

        // Red inside, white outside. A single red outline fails 3:1 on navy.
        $this->assertMatchesRegularExpression(
            '/:focus-visible\s*\{[^}]*outline:\s*2px solid #FFFFFF;[^}]*\}/i',
            $css,
            'The focus indicator must draw a white outer band, otherwise it is invisible on the navy sections.'
        );
        $this->assertMatchesRegularExpression(
            '/:focus-visible\s*\{[^}]*box-shadow:[^;]*#E31937/i',
            $css,
            'The focus indicator must keep a red inner band so it is visible on light surfaces.'
        );
    }

    public function test_components_do_not_override_the_shared_focus_ring(): void
    {
        $offenders = [];

        foreach (glob(resource_path('views/**/*.blade.php')) ?: [] as $file) {
            if (str_contains($file, 'errors')) {
                continue; // standalone documents with their own <style>
            }

            $contents = (string) file_get_contents($file);
            if (preg_match_all('/focus-visible:outline[-a-z0-9\/\.\[\]%]+/', $contents, $matches)) {
                $offenders[basename($file)] = array_unique($matches[0]);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These templates re-declare the focus ring and will drift from the global rule:\n"
                . json_encode($offenders, JSON_PRETTY_PRINT)
        );
    }

    /* ── Focus management ──────────────────────────────────── */

    public function test_the_contact_wizard_moves_focus_to_the_next_step(): void
    {
        $html = $this->get('/contact')->assertOk()->getContent();

        // The wizard behaviour lives in its own module rather than an inline
        // <script>, so the guard has to read that file. Every step replaces its
        // controls, so focus must be re-applied each time.
        $script = (string) file_get_contents(resource_path('js/contact.js'));

        $this->assertStringContainsString(
            'focusCurrentControl',
            $script,
            'The contact wizard must move focus to the new question after each answer.'
        );

        // ...onto something real, not just into the void.
        foreach (['options', 'textInput', 'textareaInput'] as $ref) {
            $this->assertStringContainsString(
                "x-ref=\"{$ref}\"",
                $html,
                "focusCurrentControl() targets \${$ref}, but no element declares it."
            );
        }
    }

    public function test_the_contact_wizard_labels_each_speaker(): void
    {
        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('Webxpress says:', $html);
        $this->assertStringContainsString('You said:', $html);
    }

    public function test_dismissing_the_mobile_menu_returns_focus_to_its_trigger(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(
            'x-ref="menuToggle"',
            $html,
            'The mobile menu toggle must be referenceable so focus can be restored.'
        );
        // Escape and the close button must restore; following a link must not.
        $this->assertStringContainsString('@keydown.escape="close(true)"', $html);
        $this->assertStringContainsString('@click="close(true)"', $html);
    }

    public function test_closing_the_services_dropdown_returns_focus_to_its_trigger(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-services-trigger', $html);
        $this->assertStringContainsString(
            'trigger.focus()',
            (string) file_get_contents(resource_path('js/app.js')),
            'Escape on the services dropdown must move focus back to the button.'
        );
    }

    public function test_the_services_dropdown_is_closed_until_the_trigger_is_used(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/id="services-menu"[^>]*hidden[^>]*data-services-menu/s',
            $html,
            'The services panel must stay closed until its trigger is used.'
        );
        $this->assertStringContainsString('data-services-trigger', $html);
        $this->assertStringContainsString("menu.hidden ? openMenu() : closeMenu()", (string) file_get_contents(resource_path('js/app.js')));
    }

    public function test_load_more_announces_results_and_keeps_focus_alive(): void
    {
        $category = Category::create(['name' => 'Technology', 'slug' => 'technology']);

        // Enough posts that the index paginates and the "Load more" control renders.
        foreach (range(1, 15) as $n) {
            Post::create([
                'category_id' => $category->id,
                'title' => "Article {$n}",
                'slug' => "article-{$n}",
                'body' => '<p>Body.</p>',
                'published_at' => now()->subDays($n),
                'is_published' => true,
            ]);
        }

        $html = $this->get('/insights')->assertOk()->getContent();

        // A live region, or the append is silent for a screen reader.
        $this->assertMatchesRegularExpression(
            '/role="status"[^>]*aria-live="polite"/',
            $html,
            'Loading more insights must be announced.'
        );

        // Focusing the first new card means the next Tab continues into the new
        // content instead of restarting from the document top. This lives in the
        // insights module, not in the inline markup of the rendered page.
        $this->assertStringContainsString(
            "firstNew.querySelector('h2')?.focus(",
            (string) file_get_contents(resource_path('js/insights.js')),
            'After appending a batch, focus must land on the first new card heading.'
        );
    }

    public function test_insight_cards_expose_a_focusable_heading_for_load_more_to_target(): void
    {
        $category = Category::create(['name' => 'Technology', 'slug' => 'technology']);
        Post::create([
            'category_id' => $category->id,
            'title' => 'A Card Heading',
            'slug' => 'a-card-heading',
            'body' => '<p>Body.</p>',
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $html = $this->get('/insights')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<h2 tabindex="-1"/',
            $html,
            'Card headings need tabindex="-1" so focus can land on them without adding a tab stop.'
        );
    }

    /* ── Orientation and semantics ─────────────────────────── */

    public function test_active_navigation_state_is_exposed_and_not_colour_only(): void
    {
        // /about is in the current nav set, so this page has an active item.
        $html = $this->get('/about')->assertOk()->getContent();

        $this->assertStringContainsString(
            'aria-current="page"',
            $html,
            'The current page is indicated by colour alone, which screen readers cannot perceive.'
        );
    }

    public function test_breadcrumb_trails_mark_their_final_item(): void
    {
        $service = ServiceCatalog::all();
        $slug = array_key_first($service);

        $serviceHtml = $this->get("/services/{$slug}")->assertOk()->getContent();
        $this->assertMatchesRegularExpression(
            '/<li aria-current="page"/',
            $serviceHtml,
            'The service breadcrumb does not mark where the trail ends.'
        );

        $projectSlug = array_key_first(ProjectCatalog::all());
        $workHtml = $this->get("/work/{$projectSlug}")->assertOk()->getContent();
        $this->assertMatchesRegularExpression(
            '/<li aria-current="page"/',
            $workHtml,
            'The work breadcrumb does not mark where the trail ends.'
        );
    }

    public function test_focusable_marquees_declare_a_role(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Each marquee is focusable so a keyboard user can pause it. Without a
        // role, tabbing onto it announces nothing useful.
        $this->assertDoesNotMatchRegularExpression(
            '/<div(?![^>]*role=)[^>]*class="wx-(client-marquee|testimonials-window)"[^>]*tabindex="0"/',
            $html,
            'A focusable marquee with no role is announced as an unlabelled group.'
        );
    }

    /**
     * @dataProvider publicPageProvider
     */
    public function test_every_public_page_declares_a_language(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<html[^>]*lang="[a-zA-Z-]+"/',
            $html,
            "{$uri} has no lang attribute, so screen readers cannot pick a pronunciation."
        );
    }

    /**
     * @dataProvider publicPageProvider
     */
    public function test_every_public_page_has_exactly_one_h1(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertSame(
            1,
            substr_count(strtolower($html), '<h1'),
            "{$uri} should have exactly one h1; heading level is how screen reader users navigate."
        );
    }

    /**
     * @dataProvider publicPageProvider
     */
    public function test_images_declare_alternative_text(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        preg_match_all('/<img\b[^>]*>/i', $html, $matches);

        foreach ($matches[0] as $img) {
            $this->assertMatchesRegularExpression(
                '/\balt\s*=/i',
                $img,
                "{$uri} has an <img> with no alt attribute: {$img}"
            );
        }
    }

    /* ── Motion ────────────────────────────────────────────── */

    public function test_back_to_top_respects_reduced_motion(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString(
            "window.scrollTo({ top: 0, behavior: 'smooth' })",
            $html,
            'An unconditional smooth scroll ignores prefers-reduced-motion.'
        );
    }
}
