<?php

namespace Tests\Feature\Security;

use App\Models\Deal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The content security policy.
 *
 * The point of this policy is that it can be strict: the audit in config/csp.php
 * found no third-party scripts, so there is no list of external origins to be
 * maintained. Every test here is either "the policy is still strict" or "the two
 * places it is deliberately not strict are still only those two places", because
 * that is the regression that matters — someone adds a CDN script, or reaches for
 * 'unsafe-inline' to fix a blank screen, and everything quietly widens.
 */
class CspTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function directives(string $header): array
    {
        $directives = [];

        foreach (explode('; ', $header) as $part) {
            $bits = explode(' ', $part, 2);
            $directives[$bits[0]] = $bits[1] ?? '';
        }

        return $directives;
    }

    private function publicPolicy(string $path): array
    {
        $response = $this->get($path);

        $response->assertHeader('Content-Security-Policy');

        return $this->directives((string) $response->headers->get('Content-Security-Policy'));
    }

    private function nonceFrom(string $header): string
    {
        $this->assertMatchesRegularExpression("/nonce-([a-f0-9]{32})/", $header, 'header names no nonce');

        preg_match("/nonce-([a-f0-9]{32})/", $header, $m);

        return $m[1];
    }

    public function test_public_pages_send_a_content_security_policy(): void
    {
        $this->get('/')->assertHeader('Content-Security-Policy');
        $this->get('/contact')->assertHeader('Content-Security-Policy');
    }

    public function test_the_public_policy_locks_every_source_to_self(): void
    {
        $d = $this->publicPolicy('/');

        $this->assertSame("'self'", $d['default-src']);
        $this->assertSame("'self'", $d['base-uri']);
        $this->assertSame("'self'", $d['connect-src']);
        $this->assertSame("'self'", $d['form-action']);
        $this->assertSame("'self'", $d['frame-ancestors']);
        $this->assertSame("'self' data:", $d['font-src']);
        $this->assertSame("'none'", $d['object-src']);
        $this->assertSame("'none'", $d['media-src']);
    }

    public function test_public_scripts_and_styles_cannot_run_inline_without_a_nonce(): void
    {
        $d = $this->publicPolicy('/');

        // The two that would nullify everything. A nonce is only named in
        // script-src/style-src here, and a nonce names a single value per
        // request — inline code an attacker injects has no nonce and must not
        // execute. Anything else in these directives is the regression.
        $this->assertStringContainsString("'self'", $d['script-src']);
        $this->assertStringNotContainsString("'unsafe-inline'", $d['script-src']);
        $this->assertMatchesRegularExpression('/\'nonce-[a-f0-9]{32}\'/', $d['script-src']);

        $this->assertStringContainsString("'self'", $d['style-src']);
        $this->assertStringNotContainsString("'unsafe-inline'", $d['style-src']);
        $this->assertMatchesRegularExpression('/\'nonce-[a-f0-9]{32}\'/', $d['style-src']);

        // style-src-attr is the accepted exception: 67 inline style attributes
        // sit across 22 views, and a nonce cannot be attached to an attribute.
        $this->assertSame("'unsafe-inline'", $d['style-src-attr'] ?? null);
    }

    public function test_the_nonce_in_the_header_matches_the_inline_blocks_in_the_markup(): void
    {
        $response = $this->get('/');
        $header = (string) $response->headers->get('Content-Security-Policy');

        $nonce = $this->nonceFrom($header);
        $html = (string) $response->getContent();

        $this->assertStringContainsString(
            '<script nonce="'.$nonce.'">window.APP_VERSION',
            $html,
            'the APP_VERSION inline script does not carry the nonce the header names'
        );

        $this->assertStringContainsString(
            '<style nonce="'.$nonce.'">.animate-on-scroll',
            $html,
            'the noscript fallback style does not carry the nonce — no-JS visitors would get stuck invisible'
        );
    }

    public function test_the_contact_page_inline_endpoint_and_styles_are_nonced(): void
    {
        $response = $this->get('/contact');
        $nonce = $this->nonceFrom((string) $response->headers->get('Content-Security-Policy'));
        $html = (string) $response->getContent();

        $this->assertStringContainsString(
            'nonce="'.$nonce.'">window.CONTACT_ENDPOINT',
            $html,
            'the contact page ships its endpoint via an unnonced inline script — the exact sink CSP exists to gate'
        );
    }

    public function test_the_token_gated_deal_portal_keeps_the_strict_policy(): void
    {
        // The portal is the highest-value target on the public site: it is
        // reachable with no login and shows a client their contract and
        // payment status. It is not under the panel segment, so it must get the
        // strict policy, not the administrative waiver.
        $deal = Deal::factory()->create(['access_token' => 'strict-csp-'.Str::random(16)]);

        $d = $this->publicPolicy('/d/'.$deal->access_token);

        $this->assertStringNotContainsString("'unsafe-inline'", $d['script-src']);
        $this->assertMatchesRegularExpression('/\'nonce-[a-f0-9]{32}\'/', $d['script-src']);
    }

    public function test_the_admin_panel_gets_the_deliberate_framework_waiver_and_nothing_more(): void
    {
        $response = $this->get('/'.admin_path().'/login');

        $response->assertHeader('Content-Security-Policy');
        $header = (string) $response->headers->get('Content-Security-Policy');
        $d = $this->directives($header);

        // Filament and Livewire neither add nonces nor accept them, so the panel
        // needs 'unsafe-inline' for its own inline scripts. This test is the
        // fence: the waiver must never leak to page-src or anything else.
        $this->assertStringContainsString("'unsafe-inline'", $d['script-src']);
        $this->assertStringContainsString("'self'", $d['script-src']);
        $this->assertStringNotContainsString("'nonce-", $d['script-src']);

        // The base policy must still hold behind the waiver.
        $this->assertSame("'none'", $d['object-src']);
    }

    public function test_a_document_under_a_page_slug_sharing_the_panel_prefix_is_not_admin(): void
    {
        // A slug like "admin-tools" must not inherit the panel's looser policy
        // just because it shares the prefix. Guarded by the path-boundary check
        // in the middleware.
        $this->assertStringNotContainsString(
            "'unsafe-inline'",
            $this->publicPolicy('/'.admin_path().'-tools')['script-src'],
            'a non-panel path matching the segment prefix received the admin waiver'
        );
    }

    public function test_error_pages_are_covered_by_the_policy(): void
    {
        $response = $this->get('/this-page-does-not-exist');

        $response->assertNotFound();
        $this->assertTrue($response->headers->has('Content-Security-Policy'));

        $d = $this->directives((string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString("'unsafe-inline'", $d['script-src']);
    }

    public function test_report_only_switches_the_header(): void
    {
        config(['csp.report_only' => true]);

        $response = $this->get('/');

        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $response->assertHeader('Content-Security-Policy-Report-Only');

        // The policy itself is unchanged when report-only; only the channel is.
        $this->assertStringNotContainsString(
            "'unsafe-inline'",
            $this->directives((string) $response->headers->get('Content-Security-Policy-Report-Only'))['script-src']
        );
    }

    public function test_the_policy_can_be_disabled(): void
    {
        config(['csp.enabled' => false]);

        $response = $this->get('/');

        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_the_vite_dev_server_origins_are_allowed_while_the_hot_file_is_present(): void
    {
        $hot = public_path('hot');
        $existed = file_exists($hot);
        file_put_contents($hot, 'http://localhost:5173');

        try {
            $d = $this->publicPolicy('/');

            $this->assertStringContainsString('http://localhost:5173', $d['connect-src']);
            $this->assertStringContainsString('ws://localhost:5173', $d['connect-src']);
            $this->assertStringContainsString("'self'", $d['script-src']);
        } finally {
            if (! $existed) {
                @unlink($hot);
            }
        }
    }
}