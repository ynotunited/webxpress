<?php

namespace Tests\Feature\Public;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The CMS catch-all: what makes "create a page in the admin" mean something.
 *
 * Without it, a Page record with no bespoke Blade template renders nowhere —
 * the editor saves the form, sees no error, and the site is unchanged. These
 * tests pin the four properties that make that loop honest: a new page really
 * renders, it renders its own copy, a draft is not public, and the catch-all
 * never steals a URL that already belongs to something.
 */
class GenericPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_page_with_no_bespoke_template_renders_at_its_own_slug(): void
    {
        $page = Page::create([
            'slug' => 'careers',
            'title' => 'Careers',
            'eyebrow' => 'Join us',
            'headline' => 'Build things that matter.',
            'intro' => 'We hire rarely and carefully.',
            'is_published' => true,
        ]);

        $body = $this->get('/careers')->assertOk()->getContent();

        $this->assertStringContainsString('Build things that matter.', $body);
        $this->assertStringContainsString('We hire rarely and carefully.', $body);
        $this->assertStringContainsString('Join us', $body);
    }

    public function test_a_page_renders_its_body_as_rich_text(): void
    {
        Page::create([
            'slug' => 'handbook',
            'title' => 'Handbook',
            'is_published' => true,
            'content' => '<h2>How we work</h2><p>We ship in <strong>small batches</strong>.</p>',
        ]);

        $body = $this->get('/handbook')->assertOk()->getContent();

        $this->assertStringContainsString('<h2>How we work</h2>', $body);
        $this->assertStringContainsString('<strong>small batches</strong>', $body);
    }

    public function test_a_page_renders_its_blocks_in_the_order_they_were_arranged(): void
    {
        $page = $this->page(['slug' => 'landing', 'is_published' => true]);

        $second = PageSection::create([
            'page_id' => $page->id, 'type' => 'section_head', 'key' => 'second', 'sort_order' => 20,
            'data' => ['heading' => 'Second section'],
        ]);
        $first = PageSection::create([
            'page_id' => $page->id, 'type' => 'section_head', 'key' => 'first', 'sort_order' => 10,
            'data' => ['heading' => 'First section'],
        ]);

        $body = $this->get('/landing')->assertOk()->getContent();

        $this->assertLessThan(
            strpos($body, 'Second section'),
            strpos($body, 'First section'),
            'blocks must render in sort_order, not in insertion order'
        );

        $this->assertTrue($first->exists && $second->exists);
    }

    public function test_an_unpublished_page_is_not_public(): void
    {
        Page::create([
            'slug' => 'secret',
            'title' => 'Secret',
            'headline' => 'Not live yet',
            'is_published' => false,
        ]);

        $this->get('/secret')->assertNotFound();
    }

    public function test_an_unpublished_page_is_still_previewable_by_an_admin(): void
    {
        Page::create([
            'slug' => 'secret',
            'title' => 'Secret',
            'headline' => 'Not live yet',
            'is_published' => false,
        ]);

        config(['admin.email' => 'boss@webxpresstech.com.ng']);
        $admin = User::factory()->create(['email' => 'boss@webxpresstech.com.ng']);

        // A draft has to be reviewable before it is published, or "publish" is
        // the only way to see the page at all.
        $this->actingAs($admin)
            ->get('/preview/pages/secret')
            ->assertOk()
            ->assertSee('Not live yet');
    }

    public function test_preview_is_closed_to_anonymous_and_non_admin_users(): void
    {
        Page::create(['slug' => 'secret', 'title' => 'Secret', 'is_published' => false]);
        config(['admin.email' => 'boss@webxpresstech.com.ng']);

        // Preview renders through the public controller stack, so it is not
        // covered by the panel's own auth. Without an explicit check here a
        // draft page is readable by anyone who guesses the URL.
        $this->get('/preview/pages/secret')->assertForbidden();

        $this->actingAs(User::factory()->create(['email' => 'intern@webxpresstech.com.ng']))
            ->get('/preview/pages/secret')
            ->assertForbidden();
    }

    public function test_previewing_an_unknown_slug_is_a_404(): void
    {
        config(['admin.email' => 'boss@webxpresstech.com.ng']);
        $admin = User::factory()->create(['email' => 'boss@webxpresstech.com.ng']);

        $this->actingAs($admin)
            ->get('/preview/pages/no-such-page')
            ->assertNotFound();
    }

    /**
     * The catch-all is registered last precisely so it cannot shadow anything.
     * These are the paths that would break if its position ever moved up.
     */
    public function test_the_catch_all_does_not_steal_a_url_that_belongs_to_something_else(): void
    {
        // The two prose routes are served by named routes, so a page record has
        // to exist for them to resolve at all. Asserting on them without this
        // would be asserting that they 404, which is a different claim.
        Page::create(['slug' => 'privacy', 'title' => 'Privacy', 'is_published' => true]);
        Page::create(['slug' => 'terms', 'title' => 'Terms', 'is_published' => true]);

        // The catch-all is registered last precisely so it cannot shadow
        // anything. These are the paths that would break if its position ever
        // moved up in web.php.
        foreach (['/', '/work', '/services', '/about', '/industries', '/insights', '/contact', '/privacy', '/terms'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_the_legal_routes_404_when_their_page_is_unpublished(): void
    {
        Page::create(['slug' => 'privacy', 'title' => 'Privacy', 'is_published' => false]);

        // The named /privacy route used to be wired to a hardcoded closure, so
        // it rendered regardless of publish state. The publish flag has to be
        // real here, not just on the catch-all.
        $this->get('/privacy')->assertNotFound();
    }

    public function test_reserved_root_segments_are_not_claimable_by_a_page(): void
    {
        // A page created for one of these would 500 the app if the catch-all
        // won, so the route pattern refuses them outright.
        Page::create(['slug' => 'admin', 'title' => 'Hostile', 'is_published' => true]);

        $this->get('/'.admin_path().'/login')->assertOk();
    }

    public function test_a_page_body_cannot_inject_script_into_the_page(): void
    {
        Page::create([
            'slug' => 'hostile',
            'title' => 'Hostile',
            'is_published' => true,
            'content' => '<p>Safe.</p><script>alert(1)</script>'
                .'<img src=x onerror="alert(2)">'
                .'<a href="javascript:alert(3)">click</a>',
        ]);

        $body = $this->get('/hostile')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $body);
        $this->assertStringNotContainsString('onerror=', $body);
        $this->assertStringNotContainsString('javascript:alert(3)', $body);

        // The safe part of the body still renders — sanitising is not the same
        // as discarding the page.
        $this->assertStringContainsString('Safe.', $body);
    }

    public function test_a_page_with_no_body_and_no_blocks_still_renders_a_heading(): void
    {
        // A half-finished draft should be visible to its author rather than
        // looking like a broken page.
        Page::create(['slug' => 'empty', 'title' => 'Empty', 'is_published' => true]);

        $this->get('/empty')
            ->assertOk()
            ->assertSee('Empty');
    }

    public function test_the_page_headline_falls_back_to_the_title(): void
    {
        Page::create(['slug' => 'untitled', 'title' => 'A Useful Title', 'is_published' => true]);

        $this->get('/untitled')->assertOk()->assertSee('A Useful Title');
    }

    private function page(array $attributes = []): Page
    {
        return Page::create(array_merge([
            'title' => 'Page',
            'is_published' => true,
        ], $attributes));
    }
}
