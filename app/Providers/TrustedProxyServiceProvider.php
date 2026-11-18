<?php

namespace App\Providers;

use App\Http\Middleware\ForceHttps;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\ServiceProvider;

/**
 * Configures edge behaviour from config rather than env().
 *
 * The old bootstrap/app.php read TRUSTED_PROXIES and FORCE_HTTPS with env()
 * while the middleware stack was being built. That works in development and
 * fails in production: once config:cache is active, .env is not loaded, so
 * Cloudflare stops being trusted and genuine Paystack webhooks are rejected
 * as coming from an edge IP.
 *
 * Providers boot after configuration is available, including cached config.
 */
class TrustedProxyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $proxies = config('app.trusted_proxies');
        $headers = (int) (config('app.trusted_headers') ?? 0);

        if (filled($proxies)) {
            TrustProxies::at($proxies);
        }

        if ($headers > 0) {
            TrustProxies::withHeaders($headers);
        }

        if (! (bool) config('app.force_https', false)) {
            return;
        }

        // Pushed, not prepended. TrustProxies is already in the global stack,
        // so by the time this runs X-Forwarded-Proto has been resolved from a
        // trusted source. Resolving it earlier would cause redirect loops.
        //
        // During an HTTP request the kernel is resolved before providers boot,
        // so afterResolving alone would never fire. Handle that already-resolved
        // case directly and keep the callback for console contexts that have
        // not resolved the HTTP kernel yet.
        if ($this->app->resolved(HttpKernel::class)) {
            $this->app->make(HttpKernel::class)->pushMiddleware(ForceHttps::class);

            return;
        }

        $this->app->afterResolving(HttpKernel::class, function (HttpKernel $kernel): void {
            $kernel->pushMiddleware(ForceHttps::class);
        });
    }
}
