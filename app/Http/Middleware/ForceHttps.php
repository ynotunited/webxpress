<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects plain HTTP requests to HTTPS.
 *
 * Laravel has no built-in force-HTTPS middleware in this version, so the old
 * `FORCE_HTTPS` switch only made session cookies secure and prepended a second
 * TrustProxies instance. It never redirected anyone.
 *
 * This middleware relies on the trusted-proxy configuration done at bootstrap.
 * Behind Cloudflare or a Hostinger SSL terminator, `$request->secure()` is only
 * correct after X-Forwarded-Proto has been accepted from a trusted source.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->secure()) {
            return redirect()->secure($request->getRequestUri(), Response::HTTP_MOVED_PERMANENTLY);
        }

        return $next($request);
    }
}
