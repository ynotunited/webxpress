<?php

use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global, not tied to the web group: the panel, the error pages and the
        // deal portal all need the header, and a route registered outside that
        // group would silently ship without one.
        $middleware->append(ContentSecurityPolicy::class);

        // Trusted proxies and HTTPS redirection are configured in
        // TrustedProxyServiceProvider. They cannot be read here: this callback
        // runs before the configuration repository is bound, and env() would
        // stop seeing .env once config:cache is used in production.

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            // The panel's email verification gates pages behind `verified`, by
            // name, when ->emailVerification() is enabled. Filament resolves the
            // alias rather than the class, so without this the panel would 500
            // the moment verification was switched on.
            'verified' => EnsureEmailIsVerified::class,
        ]);

        // Configure rate limiting for API-like endpoints and general abuse protection
        // Global rate limiting can be applied via middleware in routes
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Context on every logged line. Without it the daily log is a wall of
        // stack traces with no way to tell which request, or which logged-in
        // user, produced any of them — and these are the lines someone reads at
        // 2am to work out why a visitor saw a 500.
        $exceptions->context(fn (): array => [
            'user_id' => auth()->id(),
            'route' => request()->route()?->getName(),
            'url' => request()->fullUrl(),
        ]);

        // A JSON error contract for the two XHR endpoints on the site.
        //
        // Without this, an unexpected failure answered the insights fetch and
        // the contact wizard with either Laravel's default {"message":"Server
        // Error"} or a full HTML debug page. Neither gives the front-end
        // anything it can put on screen, and the HTML page is a megabyte of
        // markup to download in order to learn nothing.
        $exceptions->render(function (Throwable $e, Request $request) {
            // ajax() rather than expectsJson(). expectsJson() only returns true
            // for an XHR that also accepts anything, so a request carrying
            // X-Requested-With with an explicit Accept: text/html — which is
            // what a browser fetch() looks like as soon as anything sets an
            // Accept header, and what a proxy may rewrite — falls through to
            // the HTML renderer. The question being asked here is not what the
            // client will accept, it is whether the caller is JavaScript.
            if (! $request->ajax() && ! $request->expectsJson()) {
                return null;
            }

            // Deliberately narrow. HttpException already renders correctly, and
            // ValidationException carries the per-field `errors` bag the
            // contact wizard reads; intercepting either would break behaviour
            // that works. This only replaces the response for an unexpected
            // failure — the case with no useful message at all.
            if ($e instanceof HttpExceptionInterface || $e instanceof ValidationException) {
                return null;
            }

            // The exception's own message is never echoed back, even with
            // APP_DEBUG on: this response goes to a browser, and a raw message
            // can carry a query, a path or a credential. The detail belongs in
            // the log, where the context above now says who and what.
            return response()->json([
                'message' => 'Something went wrong on our end. Please try again in a moment.',
            ], 500);
        });
    })->create();
