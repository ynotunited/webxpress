<?php

namespace Tests\Feature\Cms;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Branding must resolve from one place. These tests pin the resolver
 * contract so the logo can never need editing in six templates again.
 */
class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_logo_falls_back_to_the_shipped_default(): void
    {
        $this->assertSame('/images/logo-colored.png', brand_logo('colored'));
        $this->assertSame('/images/logo-white.png', brand_logo('white'));
        $this->assertSame('/favicon.png', brand_favicon());
    }

    public function test_logo_resolves_a_public_storage_path(): void
    {
        SiteSetting::setValue('logo_colored', 'branding/new-logo.png');

        $this->assertStringEndsWith('/storage/branding/new-logo.png', brand_logo('colored'));
    }

    public function test_logo_passes_through_an_absolute_url(): void
    {
        SiteSetting::setValue('logo_colored', 'https://cdn.example.com/logo.svg');

        $this->assertSame('https://cdn.example.com/logo.svg', brand_logo('colored'));
    }

    public function test_logo_passes_through_a_root_relative_path(): void
    {
        SiteSetting::setValue('logo_colored', '/custom/logo.png');

        $this->assertSame('/custom/logo.png', brand_logo('colored'));
    }

    public function test_blank_or_cleared_settings_fall_back_to_the_default(): void
    {
        SiteSetting::setValue('logo_colored', '');
        $this->assertSame('/images/logo-colored.png', brand_logo('colored'));

        SiteSetting::setValue('logo_colored', null);
        $this->assertSame('/images/logo-colored.png', brand_logo('colored'));
    }

    public function test_unknown_logo_variant_falls_back_to_colored(): void
    {
        $this->assertSame('/images/logo-colored.png', brand_logo('rainbow'));
    }

    public function test_site_name_falls_back_to_app_name(): void
    {
        $this->assertSame(config('app.name'), site_name());

        SiteSetting::setValue('site_name', 'Acme Digital');
        $this->assertSame('Acme Digital', site_name());
    }

    public function test_public_pages_use_the_shared_logo_helper(): void
    {
        SiteSetting::setValue('logo_colored', 'branding/custom-colored.png');
        SiteSetting::setValue('logo_white', 'branding/custom-white.png');

        foreach (['/', '/about', '/contact', '/services'] as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();

            $this->assertStringContainsString('/storage/branding/custom-colored.png', $html, "header logo missing on {$uri}");
            $this->assertStringNotContainsString('/images/logo-colored.png', $html, "hardcoded logo still on {$uri}");
        }
    }

    public function test_the_shared_footer_carries_no_logo(): void
    {
        // The shared footer is wordmark-free by design: the brand mark already
        // sits in the header and the copyright line names the company. Pinning
        // the absence keeps a logo from creeping back in through a hardcoded
        // path, which the branding settings could never reach or update.
        SiteSetting::setValue('logo_colored', 'branding/custom-colored.png');
        SiteSetting::setValue('logo_white', 'branding/custom-white.png');

        $html = $this->get('/about')->assertOk()->getContent();

        $this->assertStringNotContainsString('/storage/branding/custom-white.png', $html);
        $this->assertStringNotContainsString('/images/logo-white.png', $html);
    }

    public function test_the_contact_pages_dark_panel_uses_the_shared_white_logo(): void
    {
        // The contact page closes on its own dark panel, and that panel is the
        // one place the white mark is actually used — so this is where the
        // white variant has to resolve from settings.
        SiteSetting::setValue('logo_colored', 'branding/custom-colored.png');
        SiteSetting::setValue('logo_white', 'branding/custom-white.png');

        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('/storage/branding/custom-white.png', $html);
        $this->assertStringNotContainsString('/images/logo-white.png', $html);
    }

    public function test_favicon_uses_the_shared_helper(): void
    {
        SiteSetting::setValue('favicon', 'branding/custom-favicon.png');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('/storage/branding/custom-favicon.png', $html);
        $this->assertStringNotContainsString('href="/favicon.png"', $html);
    }

    public function test_site_name_flows_into_logo_alt_text(): void
    {
        SiteSetting::setValue('site_name', 'Acme Digital');

        $this->get('/')->assertOk()->assertSee('alt="Acme Digital"', false);
    }

    public function test_social_preview_image_falls_back_to_the_shipped_asset(): void
    {
        $this->assertSame(asset('images/og/webxpress-preview.png'), og_image_url());
    }

    public function test_social_preview_image_resolves_settings_like_other_brand_assets(): void
    {
        SiteSetting::setValue('og_image', 'branding/share.png');
        $this->assertStringEndsWith('/storage/branding/share.png', og_image_url());

        SiteSetting::setValue('og_image', '/custom/share.png');
        $this->assertSame(url('/custom/share.png'), og_image_url());

        SiteSetting::setValue('og_image', 'https://cdn.example.com/share.png');
        $this->assertSame('https://cdn.example.com/share.png', og_image_url());
    }

    public function test_public_pages_emit_an_absolute_preview_image_with_dimensions(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:image" content="'.og_image_url().'">', $html);
        $this->assertStringContainsString('<meta property="og:image:width" content="1200">', $html);
        $this->assertStringContainsString('<meta property="og:image:height" content="630">', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
    }

    public function test_a_page_without_its_own_og_title_uses_the_site_setting(): void
    {
        SiteSetting::setValue('og_title', 'Shared preview title');

        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:title" content="Shared preview title">', $html);
    }

    public function test_admin_site_settings_exposes_the_branding_uploader(): void
    {
        // The panel is locked to the configured admin email, so naming the user
        // is part of reaching the screen under test.
        config(['admin.email' => 'boss@webxpresstech.com.ng']);

        $admin = User::factory()->create(['email' => 'boss@webxpresstech.com.ng']);

        $response = $this->actingAs($admin)->get('/'.admin_path().'/site-settings');

        $response->assertOk();
        $response->assertSee('Branding');
        $response->assertSee('Open Graph title');
        $response->assertSee('Open Graph image');
        $response->assertSee('Logo (light backgrounds)');
        $response->assertSee('Favicon');
    }
}
