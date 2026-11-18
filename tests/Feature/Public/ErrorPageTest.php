<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Branded error pages.
 *
 * The shell is deliberately self-contained: no @vite, no database reads. A
 * 500 is often a database or asset-build outage, so a page that depends on
 * either would fail exactly when it is needed most.
 */
class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_route_renders_the_branded_404(): void
    {
        $response = $this->get('/definitely-not-a-real-page');

        $response->assertNotFound();
        $response->assertSee('404');
        $response->assertSee('Page not found');
    }

    public function test_branded_404_is_noindex(): void
    {
        $this->get('/definitely-not-a-real-page')
            ->assertNotFound()
            ->assertSee('noindex', false);
    }

    public function test_error_pages_avoid_the_asset_build(): void
    {
        // A missing manifest must not stop the 404 from rendering.
        $manifest = public_path('build/manifest.json');
        $backup = is_file($manifest) ? file_get_contents($manifest) : null;

        if ($backup !== null) {
            unlink($manifest);
        }

        try {
            $this->get('/definitely-not-a-real-page')
                ->assertNotFound()
                ->assertSee('Page not found');
        } finally {
            if ($backup !== null) {
                file_put_contents($manifest, $backup);
            }
        }
    }

    public function test_branded_404_does_not_read_from_the_database(): void
    {
        // The shell must not call any helper that queries the database,
        // because a 500 is frequently a database outage.
        $shell = file_get_contents(resource_path('views/errors/_shell.blade.php'));

        foreach (['site_setting', 'brand_logo', 'brand_favicon', 'site_name', 'site_page'] as $helper) {
            $this->assertStringNotContainsString($helper.'(', $shell, "error shell calls {$helper}()");
        }
    }

    public function test_error_shell_view_exists_for_the_expected_status_codes(): void
    {
        foreach ([404, 500] as $code) {
            $this->assertFileExists(resource_path("views/errors/{$code}.blade.php"));
        }
    }

    public function test_error_shell_inlines_its_own_styles(): void
    {
        $html = $this->get('/definitely-not-a-real-page')->assertNotFound()->getContent();

        $this->assertStringContainsString('<style nonce="', $html);
        $this->assertStringNotContainsString('/build/assets/', $html);
    }

    public function test_branded_404_offers_a_way_forward(): void
    {
        $html = $this->get('/definitely-not-a-real-page')->assertNotFound()->getContent();

        $this->assertStringContainsString('href="/contact"', $html);
    }

    public function test_forbidden_users_get_a_branded_403(): void
    {
        Route::middleware('web')->get('/__test-forbidden', fn () => abort(403));

        $this->get('/__test-forbidden')
            ->assertForbidden()
            ->assertSee('403');
    }
}
