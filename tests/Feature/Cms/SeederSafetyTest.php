<?php

namespace Tests\Feature\Cms;

use App\Models\ClientReview;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\PageSectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard.
 *
 * A seeder that overwrites admin-edited content makes the CMS unusable: an
 * editor saves a change, someone runs `db:seed` on deploy, and the change is
 * silently gone. These tests pin that `db:seed` never clobbers content.
 */
class SeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_page_seeder_does_not_overwrite_page_body_copy(): void
    {
        $page = Page::create([
            'slug' => 'privacy',
            'title' => 'Privacy Policy',
            'content' => '<p>Hand-edited legal copy.</p>',
            'is_published' => true,
        ]);

        $this->artisan('db:seed', ['--class' => 'SitePageSeeder'])->assertSuccessful();

        $this->assertSame('<p>Hand-edited legal copy.</p>', $page->fresh()->content);
    }

    public function test_site_page_seeder_does_not_null_out_bespoke_page_bodies(): void
    {
        $page = Page::create([
            'slug' => 'about',
            'title' => 'About',
            'content' => '<p>Promoted about copy.</p>',
            'is_published' => true,
        ]);

        $this->artisan('db:seed', ['--class' => 'SitePageSeeder'])->assertSuccessful();

        $this->assertSame('<p>Promoted about copy.</p>', $page->fresh()->content);
    }

    public function test_site_page_seeder_still_syncs_seo_and_publish_state(): void
    {
        $page = Page::create([
            'slug' => 'privacy',
            'title' => 'Stale title',
            'seo_title' => 'Stale seo',
            'is_published' => false,
        ]);

        $this->artisan('db:seed', ['--class' => 'SitePageSeeder'])->assertSuccessful();

        $page->refresh();

        $this->assertSame('Privacy Policy', $page->title);
        $this->assertStringContainsString('Privacy Policy', $page->seo_title);
        $this->assertTrue($page->is_published);
    }

    public function test_site_page_seeder_is_idempotent(): void
    {
        $this->artisan('db:seed', ['--class' => 'SitePageSeeder'])->assertSuccessful();
        $countAfterFirst = Page::count();

        $this->artisan('db:seed', ['--class' => 'SitePageSeeder'])->assertSuccessful();

        $this->assertSame($countAfterFirst, Page::count());
        $this->assertSame(17, Page::count());
    }

    /**
     * The hero has to be backfilled into a database where the pages already
     * exist, or the editable hero is inert on every existing install — the
     * seeder would only ever fill blanks on a page it had itself created.
     */
    public function test_site_page_seeder_backfills_a_missing_hero(): void
    {
        $page = Page::create([
            'slug' => 'about',
            'title' => 'About',
            'eyebrow' => null,
            'headline' => null,
            'intro' => null,
            'is_published' => true,
        ]);

        $this->artisan('db:seed', ['--class' => 'SitePageSeeder'])->assertSuccessful();
        $page->refresh();

        $this->assertNotEmpty($page->headline, 'the seeder must backfill a hero into a pre-existing page');
        $this->assertSame('About Webxpress', $page->eyebrow);
        $this->assertNotEmpty($page->intro);
    }

    public function test_site_page_seeder_does_not_overwrite_an_edited_hero(): void
    {
        $page = Page::create([
            'slug' => 'about',
            'title' => 'About',
            'eyebrow' => 'Our story',
            'headline' => 'Editor-written headline.',
            'intro' => 'Editor-written intro.',
            'is_published' => true,
        ]);

        $this->artisan('db:seed', ['--class' => 'SitePageSeeder'])->assertSuccessful();
        $page->refresh();

        $this->assertSame('Our story', $page->eyebrow);
        $this->assertSame('Editor-written headline.', $page->headline);
        $this->assertSame('Editor-written intro.', $page->intro);
    }

    public function test_site_page_seeder_backfills_only_the_blank_half_of_a_hero(): void
    {
        $page = Page::create([
            'slug' => 'about',
            'title' => 'About',
            'eyebrow' => 'Our story',
            'headline' => 'Editor-written headline.',
            'intro' => null,
            'is_published' => true,
        ]);

        $this->artisan('db:seed', ['--class' => 'SitePageSeeder'])->assertSuccessful();
        $page->refresh();

        $this->assertSame('Editor-written headline.', $page->headline, 'an edited field must not be touched');
        $this->assertNotEmpty($page->intro, 'a blank field should still be filled in');
    }

    public function test_page_section_seeder_does_not_overwrite_admin_edits(): void
    {
        $home = Page::create(['slug' => 'home', 'title' => 'Home', 'is_published' => true]);
        $this->artisan('db:seed', ['--class' => 'PageSectionSeeder'])->assertSuccessful();

        $block = PageSection::where('key', 'who-we-are')->firstOrFail();
        $data = $block->data;
        $data['quote'] = 'An admin-edited quote.';
        $block->update(['data' => $data]);

        $this->artisan('db:seed', ['--class' => 'PageSectionSeeder'])->assertSuccessful();

        $this->assertSame('An admin-edited quote.', PageSection::where('key', 'who-we-are')->firstOrFail()->get('quote'));
    }

    public function test_full_seed_chain_preserves_admin_edits(): void
    {
        $home = Page::create(['slug' => 'home', 'title' => 'Home', 'is_published' => true]);
        $legal = Page::create(['slug' => 'privacy', 'title' => 'Privacy Policy', 'content' => '<p>Edited.</p>', 'is_published' => true]);

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertSame('<p>Edited.</p>', $legal->fresh()->content);

        $block = PageSection::where('key', 'who-we-are')->firstOrFail();
        $data = $block->data;
        $data['quote'] = 'Chain-edited quote.';
        $block->update(['data' => $data]);

        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertSame('Chain-edited quote.', PageSection::where('key', 'who-we-are')->firstOrFail()->get('quote'));
        $this->assertSame(1, PageSection::where('key', 'who-we-are')->count());
    }

    public function test_page_section_seeder_requires_the_home_page(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('requires the "home" page record');

        (new PageSectionSeeder)->run();
    }

    public function test_site_settings_seeder_does_not_overwrite_an_edited_setting(): void
    {
        SiteSetting::setValue('site_name', 'Editor-written name');

        $this->artisan('db:seed', ['--class' => 'SiteSettingsSeeder'])->assertSuccessful();

        $this->assertSame('Editor-written name', site_setting('site_name'));
        $this->assertSame('hello@webxpresstech.com.ng', site_setting('contact_email'));
    }

    public function test_site_settings_seeder_seeds_no_placeholder_contact_details(): void
    {
        $this->artisan('db:seed', ['--class' => 'SiteSettingsSeeder'])->assertSuccessful();

        $this->assertNull(SiteSetting::value('contact_phone'));
        $this->assertNull(SiteSetting::value('whatsapp_number'));
        $this->assertNull(SiteSetting::value('linkedin_url'));
        $this->assertNull(SiteSetting::value('instagram_url'));
        $this->assertNull(SiteSetting::value('x_url'));
    }

    public function test_full_seed_chain_creates_no_test_user_or_placeholder_reviews(): void
    {
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, User::where('email', 'test@example.com')->count());
        $this->assertSame(0, ClientReview::count());
    }
}
