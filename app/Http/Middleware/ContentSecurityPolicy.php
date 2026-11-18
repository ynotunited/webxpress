<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends the Content-Security-Policy header.
 *
 * The app has no third-party scripts, so the policy can be strict instead of a
 * list of origins somebody has to keep auditing. This middleware is what keeps
 * it that way: a CDN tag added tomorrow fails to render until the policy is
 * changed deliberately, rather than quietly widening what every page trusts.
 *
 * Two things it has to account for:
 *
 *   - Our own inline blocks are nonced. The nonce is minted here before any
 *     view renders and reused when the header is assembled, so the value in the
 *     markup and the value in the header are the same one.
 *
 *   - The admin panel gets a looser policy, because Filament and Livewire
 *     render inline scripts and styles with no nonce support. See config/csp.php
 *     for why that is unavoidable and what it costs.
 *
 * @see config/csp.php for the reasoning behind each directive.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        // Minted before $next so every view rendered downstream sees the same
        // value the header will name. Reset on each request rather than left in
        // the container, so a long-running worker cannot serve two requests
        // under one nonce.
        app()->instance('csp.nonce', bin2hex(random_bytes(16)));

        $response = $next($request);

        if (! config('csp.enabled')) {
            return $response;
        }

        $response->headers->set(
            config('csp.report_only')
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy',
            $this->header($request)
        );

        return $response;
    }

    private function header(Request $request): string
    {
        $policy = config('csp.policy', []);
        $isAdmin = $this->isAdminPath($request);

        // The admin overrides deliberately replace script-src and style-src
        // rather than append to them: adding a nonce alongside 'unsafe-inline'
        // would switch the nonce on and have the browser ignore the
        // 'unsafe-inline' entirely, breaking the panel while looking correct.
        if ($isAdmin) {
            $policy = array_merge($policy, (array) config('csp.admin', []));
        }

        // While the Vite dev server is up, @vite points at its origin and the
        // HMR client dials a WebSocket back to it. Missing these fails as a
        // blank page with nothing in the build output to explain it.
        if (file_exists(public_path('hot'))) {
            foreach ((array) config('csp.dev', []) as $directive => $sources) {
                if ($directive === 'origin') {
                    continue;
                }

                $policy[$directive] = $this->merge($policy[$directive] ?? [], $sources);
            }
        }

        if (! $isAdmin) {
            $nonce = "'nonce-".csp_nonce()."'";

            $policy['script-src'] = $this->merge($policy['script-src'] ?? [], [$nonce]);
            $policy['style-src'] = $this->merge($policy['style-src'] ?? [], [$nonce]);
        }

        if (filled(config('csp.report_uri'))) {
            $policy['report-uri'] = [(string) config('csp.report_uri')];
        }

        return collect($policy)
            ->map(fn ($sources, string $directive): string => $directive.' '.implode(' ', $sources))
            ->implode('; ');
    }

    /**
     * @param  array<int, string>  $sources
     * @return array<int, string>
     */
    private function merge(array $current, array $sources): array
    {
        return array_values(array_unique(array_merge($current, $sources)));
    }

    /**
     * Whether the request is for the admin panel — tested against the configured
     * segment rather than against /admin, and against a path boundary rather than
     * a bare prefix, so a page slugged "admin-tools" does not inherit the panel's
     * looser policy by accident.
     */
    private function isAdminPath(Request $request): bool
    {
        $segment = admin_path();
        $path = $request->path();

        return $path === $segment || str_starts_with($path, $segment.'/');
    }
}
