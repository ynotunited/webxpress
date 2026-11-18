<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * The JSON error contract for the site's two XHR endpoints.
 *
 * The insights feed and the contact wizard both render their own failure
 * notice from the response body, so the body's contents are part of the
 * contract rather than incidental output.
 *
 * The rule under test: an unexpected server failure returns a message a
 * visitor can read, and never the exception's own text.
 */
class ErrorContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Stands in for any unhandled failure in a controller or service.
        Route::middleware('web')->get('/__boom', fn () => throw new RuntimeException(
            'SQLSTATE[HY000] connection to prod-db-01.internal refused, password=hunter2'
        ));
    }

    public function test_an_unexpected_failure_returns_a_readable_message_to_an_xhr_caller(): void
    {
        $response = $this->getJson('/__boom');

        $response->assertStatus(500);
        $response->assertJsonStructure(['message']);

        $this->assertSame(
            'Something went wrong on our end. Please try again in a moment.',
            $response->json('message')
        );
    }

    public function test_the_exception_message_is_never_returned_to_the_client(): void
    {
        // A raw exception message can carry a hostname, a query or a
        // credential. Even with APP_DEBUG on, this response goes to a browser.
        config(['app.debug' => true]);

        $response = $this->getJson('/__boom');

        $body = $response->getContent();

        $this->assertStringNotContainsString('prod-db-01.internal', $body);
        $this->assertStringNotContainsString('hunter2', $body);
        $this->assertStringNotContainsString('SQLSTATE', $body);
    }

    public function test_a_browser_visitor_still_gets_the_branded_html_page(): void
    {
        // Debug off, or Ignition answers every 500 and the branded page is
        // never exercised at all — which is the case this test exists for.
        config(['app.debug' => false]);

        $response = $this->get('/__boom');

        $response->assertStatus(500);
        $this->assertStringContainsString('<html', $response->getContent());
        $this->assertStringNotContainsString('Something went wrong on our end', $response->getContent());
    }

    public function test_an_xhr_caller_with_an_explicit_html_accept_still_gets_json(): void
    {
        // The regression this guards. expectsJson() requires acceptsAnyContent
        // Type(), so X-Requested-With plus Accept: text/html resolved to false
        // and the caller received an HTML error page it could not read. The
        // caller being JavaScript is the only fact that matters here.
        $response = $this->get('/__boom', [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
        ]);

        $this->assertSame(
            'Something went wrong on our end. Please try again in a moment.',
            $response->json('message')
        );
    }

    public function test_xhr_header_alone_is_enough_to_get_json(): void
    {
        // The insights feed relies on this: it sends X-Requested-With, and the
        // server has to answer in JSON even when Accept is not set.
        $response = $this->get('/__boom', ['X-Requested-With' => 'XMLHttpRequest']);

        $this->assertSame(
            'Something went wrong on our end. Please try again in a moment.',
            $response->json('message')
        );
    }

    public function test_validation_errors_keep_their_per_field_messages(): void
    {
        Route::middleware('web')->post('/__validate', fn () => validator(
            ['email' => 'not-an-email'],
            ['email' => 'required|email']
        )->validate());

        $response = $this->postJson('/__validate', ['email' => 'not-an-email']);

        $response->assertStatus(422);
        // The contact wizard reads this bag, so it must survive the handler.
        $response->assertJsonValidationErrors('email');
    }

    public function test_a_client_error_keeps_its_own_message(): void
    {
        Route::middleware('web')->get('/__gone', fn () => abort(404, 'That insight is no longer published.'));

        $response = $this->getJson('/__gone');

        $response->assertNotFound();
        // HttpException is left to Laravel's own renderer on purpose.
        $response->assertJson(['message' => 'That insight is no longer published.']);
    }
}