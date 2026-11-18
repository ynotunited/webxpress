<?php

namespace Tests\Feature\Public;

use App\Models\Page;
use App\Support\SiteNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Site navigation.
 *
 * The menu was a hardcoded array in the header partial, so a page an editor
 * created in the admin could never appear in it and the menu could not be
 * reordered without a code change. It is now assembled from the curated core
 * links plus anything flagged show_in_nav.
 *
 * The risk in that design is duplication and dead links, and both are silent —
 * a page that appears twice looks like a bug the editor cannot fix, and a link
 * to a draft 404s in front of a customer. These tests pin both.
 */
class SiteNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_curated_core_navigation_is_always_present(): void
    {
        $labels = SiteNavigation::items()->pluck('label');

        $this->assertSame(
            ['Work', 'Services', 'About', 'Insights', 'Contact'],
            $labels->all()
        );
    }

    public function test_a_page_flagged_for_navigation_appears_in_the_menu(): void
    {
        Page::create([
            'slug' => 'careers',
            'title' => 'Careers',
            'show_in_nav' => true,
            'nav_position' => 1,
            'is_published' => true,
        ]);

        $labels = SiteNavigation::items()->pluck('label');

        $this->assertContains('Careers', $labels);
    }

    public function test_a_page_not_flagged_for_navigation_stays_out_of_the_menu(): void
    {
        Page::create([
            'slug' => 'careers',
            'title' => 'Careers',
            'show_in_nav' => false,
            'is_published' => true,
        ]);

        $this->assertNotContains('Careers', SiteNavigation::items()->pluck('label'));
    }

    public function test_an_unpublished_page_never_appears_in_the_menu(): void
    {
        // A menu link to a draft 404s. The visitor is on the public site, so
        // the page record's own publish flag has to gate the link, not just
        // the page itself.
        Page::create([
            'slug' => 'careers',
            'title' => 'Careers',
            'show_in_nav' => true,
            'is_published' => false,
        ]);

        $this->assertNotContains('Careers', SiteNavigation::items()->pluck('label'));
    }

    public function test_a_page_that_already_has_a_core_link_is_not_listed_twice(): void
    {
        // The core menu already has About and Contact. A page record for
        // either slug must not append a second "About".
        Page::create([
            'slug' => 'about',
            'title' => 'About',
            'show_in_nav' => true,
            'is_published' => true,
        ]);
        Page::create([
            'slug' => 'contact',
            'title' => 'Contact',
            'show_in_nav' => true,
            'is_published' => true,
        ]);

        $labels = SiteNavigation::items()->pluck('label');

        $this->assertSame(1, $labels->filter(fn ($l) => $l === 'About')->count());
        $this->assertSame(1, $labels->filter(fn ($l) => $l === 'Contact')->count());
    }

    public function test_a_legal_page_is_not_listed_twice_in_the_footer(): void
    {
        // Privacy and Terms are linked by the footer's unconditional legal
        // list. A page record flagged for navigation as well must not produce
        // a second identical link.
        Page::create([
            'slug' => 'privacy',
            'title' => 'Privacy Policy',
            'show_in_nav' => true,
            'is_published' => true,
        ]);

        $this->assertCount(1, SiteNavigation::legalItems());
        $this->assertNotContains('Privacy Policy', SiteNavigation::footerItems()->pluck('label'));
    }

    public function test_the_footer_omits_a_legal_page_that_is_not_published(): void
    {
        // Linking a draft policy would 404 the visitor, which is a worse
        // outcome than not linking it at all.
        Page::create([
            'slug' => 'terms',
            'title' => 'Terms of Service',
            'is_published' => false,
        ]);

        $this->assertNotContains('Terms of Service', SiteNavigation::legalItems()->pluck('label'));
    }

    public function test_the_mobile_menu_leads_with_home(): void
    {
        $labels = SiteNavigation::mobileItems()->pluck('label');

        $this->assertSame('Home', $labels->first());
    }

    public function test_a_navigation_entry_uses_its_own_route_when_one_exists(): void
    {
        // A page served by a bespoke controller must link to that controller's
        // URL, not to a catch-all path that would 404.
        Page::create([
            'slug' => 'work',
            'title' => 'Work',
            'show_in_nav' => true,
            'is_published' => true,
        ]);

        $url = Page::where('slug', 'work')->first()->url;

        $this->assertStringEndsWith('/work', $url);
        $this->assertTrue(Page::where('slug', 'work')->first()->hasDedicatedRoute());
    }

    public function test_a_navigation_entry_uses_the_catch_all_when_no_route_exists(): void
    {
        $page = Page::create([
            'slug' => 'careers',
            'title' => 'Careers',
            'show_in_nav' => true,
            'is_published' => true,
        ]);

        $this->assertStringEndsWith('/careers', $page->url);
        $this->assertFalse($page->hasDedicatedRoute());
    }

    public function test_every_navigation_link_resolves_to_a_real_page(): void
    {
        foreach (['work', 'services', 'about', 'industries', 'insights', 'contact', 'privacy', 'terms'] as $slug) {
            Page::create([
                'slug' => $slug,
                'title' => ucfirst($slug),
                'is_published' => true,
            ]);
        }

        Page::create([
            'slug' => 'careers',
            'title' => 'Careers',
            'nav_label' => 'Join the team',
            'show_in_nav' => true,
            'is_published' => true,
        ]);

        // A menu that 404s is worse than no menu, so every generated link is
        // actually followed.
        foreach (SiteNavigation::items()->merge(SiteNavigation::footerItems())->merge(SiteNavigation::legalItems()) as $item) {
            $path = parse_url($item['href'], PHP_URL_PATH);

            $this->get($path)->assertOk("the navigation link {$item['href']} does not resolve");
        }
    }

    public function test_a_navigation_label_override_is_used(): void
    {
        Page::create([
            'slug' => 'careers',
            'title' => 'Careers at Webxpress Technologies Limited',
            'nav_label' => 'Join the team',
            'show_in_nav' => true,
            'is_published' => true,
        ]);

        $labels = SiteNavigation::items()->pluck('label');

        $this->assertContains('Join the team', $labels);
        $this->assertNotContains('Careers at Webxpress Technologies Limited', $labels);
    }

    public function test_navigation_entries_follow_the_order_an_editor_set(): void
    {
        Page::create([
            'slug' => 'careers', 'title' => 'Careers', 'show_in_nav' => true,
            'nav_position' => 1, 'is_published' => true,
        ]);
        Page::create([
            'slug' => 'security', 'title' => 'Security', 'show_in_nav' => true,
            'nav_position' => 2, 'is_published' => true,
        ]);

        $appended = SiteNavigation::items()->pluck('label')->all();

        $this->assertSame(['Careers', 'Security'], array_slice($appended, -2));
    }
}
