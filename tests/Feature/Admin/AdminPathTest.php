<?php

namespace Tests\Feature\Admin;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The panel's URL segment.
 *
 * Be clear about what is being asserted here, because it is easy to overstate: a
 * non-guessable path is not an access control. It removes the cheap target — the
 * unauthenticated scanner that finds /admin and starts guessing — and it is worth
 * having for that alone. It stops nothing on its own, because the path leaks from a
 * bookmark, a Referer header, a screenshot, or anyone with read access to the
 * repository. The controls that actually hold the panel are the fail-closed
 * ADMIN_EMAIL check in PanelAccessTest and the login throttling in
 * AdminLoginThrottleTest.
 *
 * What these tests protect is a failure mode: the path is read in three places —
 * the panel provider, the CMS catch-all, and the admin emails. If moving it left
 * one of them behind, the result is a login form at an address nobody visits, or a
 * CMS page quietly shadowing the panel, or client-facing buttons that 404. Each of
 * those is silent, so each gets asserted.
 *
 * They run against whatever ADMIN_PATH the environment actually has rather than
 * overriding it, because Laravel's environment repository is repopulated from .env
 * on every application boot — a pre-set value cannot survive a refresh, and
 * rewriting .env from a test would mean the panel URL changing underneath a
 * running site if the restore ever failed.
 */
class AdminPathTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every route registered under the current panel segment.
     *
     * @return list<string>
     */
    private function panelRouteUris(): array
    {
        $segment = admin_path();
        $uris = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if ($uri === $segment || str_starts_with($uri, $segment.'/')) {
                $uris[] = $uri;
            }
        }

        return array_values(array_unique($uris));
    }

    public function test_the_panel_is_mounted_on_the_configured_segment(): void
    {
        $this->assertSame(
            admin_path(),
            Filament::getPanel('admin')->getPath(),
            'the panel provider is not reading the path from ADMIN_PATH'
        );
    }

    public function test_every_panel_route_sits_under_that_segment(): void
    {
        $uris = $this->panelRouteUris();

        $this->assertContains(admin_path().'/login', $uris);
        $this->assertContains(admin_path().'/deals', $uris);
        $this->assertContains(admin_path().'/site-settings', $uris);

        foreach ($uris as $uri) {
            $this->assertTrue(
                $uri === admin_path() || str_starts_with($uri, admin_path().'/'),
                "route registered outside the panel segment: {$uri}"
            );
        }
    }

    public function test_the_default_admin_segment_serves_nothing_once_the_path_is_changed(): void
    {
        if (admin_path() === 'admin') {
            $this->markTestSkipped('ADMIN_PATH is still the default, so there is no relocated panel to check');
        }

        // The whole point of the change: nothing answers where a scanner looks.
        $this->get('/admin/login')->assertNotFound();
        $this->get('/admin/deals')->assertNotFound();
    }

    public function test_the_panel_answers_on_the_configured_segment(): void
    {
        $this->get('/'.admin_path().'/login')->assertOk();
        $this->get('/'.admin_path().'/deals')->assertRedirect();
    }

    public function test_the_cms_catch_all_cannot_serve_a_page_on_the_panel_segment(): void
    {
        $pageRoute = collect(Route::getRoutes())->first(fn ($route): bool => $route->getName() === 'page');

        $this->assertNotNull($pageRoute);

        // Assert the slug pattern itself rather than a request. It is the exact
        // mechanism — and it holds whether or not a page row happens to exist at
        // that address, which is the case nothing else covers. Laravel stores the
        // pattern bare and adds the delimiters when it compiles, so add them here
        // too — a pattern starting with '^' would otherwise be read as using '^'
        // as its own delimiter.
        $pattern = '#'.(string) $pageRoute->wheres['slug'].'#u';

        $this->assertMatchesRegularExpression($pattern, 'insights', 'the catch-all no longer serves ordinary pages');

        $this->assertDoesNotMatchRegularExpression(
            $pattern,
            admin_path(),
            'the CMS catch-all still matches the panel segment, so a page created there would shadow the login form'
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function unusablePaths(): array
    {
        return [
            'dots and slashes' => ['../Admin Panel', 'adminpanel'],
            'leading and trailing space' => ['  spaced  ', 'spaced'],
            'uppercase' => ['MixedCase', 'mixedcase'],
            'dashes and underscores kept' => ['panel-9_x', 'panel-9_x'],
            'all symbols' => ['///', 'admin'],
            'path traversal only' => ['../..', 'admin'],
            'reserved root segments' => ['livewire', 'livewire'],
        ];
    }

    #[DataProvider('unusablePaths')]
    public function test_the_segment_is_sanitised(string $configured, string $expected): void
    {
        config(['admin.path' => $configured]);

        $this->assertSame($expected, admin_path());
    }

    public function test_a_configured_path_never_mounts_the_panel_at_the_site_root(): void
    {
        // An empty or fully-stripped value must not collapse the panel onto '/',
        // where it would sit on top of the homepage and every panel URL would
        // collide with a real page.
        config(['admin.path' => '']);
        $this->assertSame('admin', admin_path());

        config(['admin.path' => '   ']);
        $this->assertSame('admin', admin_path());

        config(['admin.path' => null]);
        $this->assertSame('admin', admin_path());
    }

    public function test_admin_url_follows_the_configured_segment(): void
    {
        $original = admin_path();

        config(['admin.path' => 'panel-Zx91qqBm4']);

        $this->assertSame(url('panel-zx91qqbm4/deals'), admin_url('deals'));
        $this->assertSame(url('panel-zx91qqbm4'), admin_url());

        config(['admin.path' => $original]);
    }

    public function test_no_email_template_hardcodes_the_panel_segment(): void
    {
        // The admin emails link into the panel. They resolve the path through
        // admin_url() so a moved panel cannot leave a live client-facing email
        // pointing at a 404 — which nothing else would catch, because no route
        // test renders these templates.
        $offenders = [];

        foreach (glob(resource_path('views/emails/*.blade.php')) ?: [] as $file) {
            $contents = (string) file_get_contents($file);

            if (preg_match('#[\'"]/admin/#', $contents) === 1) {
                $offenders[] = basename($file);
            }
        }

        $this->assertSame([], $offenders, 'link through admin_url() so ADMIN_PATH changes cannot break these buttons');
    }
}