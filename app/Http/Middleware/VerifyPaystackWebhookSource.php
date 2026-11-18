<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decides whether a request to the webhook endpoint may be processed at all.
 *
 * Runs before the controller and before signature verification, on purpose:
 * an allowlist check is cheap and an HMAC over an arbitrary attacker-supplied
 * body is work an attacker gets to choose. Rejecting first means a forged flood
 * costs nothing and never reaches the payment code.
 *
 * Neither check here is sufficient on its own. The allowlist is a second lock
 * on a door whose key is the signature — if Paystack rotates its address range
 * this layer starts rejecting genuine events, which is why it is configurable
 * and why every rejection is logged with the address actually seen. Signature
 * verification is the real defence and is not optional behind this.
 */
class VerifyPaystackWebhookSource
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = (array) config('paystack.webhook.allowed_ips', []);

        if ($allowed === []) {
            // An empty list is a misconfiguration, not an instruction to allow
            // everything. An unconfigured allowlist must not quietly become no
            // allowlist, so this is loud in the log and the request continues —
            // signature verification still stands between it and the database.
            Log::warning(
                'Paystack webhook allowlist is empty — IP filtering is not in effect. '
                .'Set paystack.webhook.allowed_ips.'
            );

            return $next($request);
        }

        $sourceIp = $this->resolveSourceIp($request);

        if (in_array($sourceIp, $allowed, true)) {
            return $next($request);
        }

        if (! config('paystack.webhook.enforce_ip_allowlist', true)) {
            Log::warning('Paystack webhook from a non-allowlisted address, allowed by configuration', [
                'source_ip' => $sourceIp,
                'enforce' => false,
            ]);

            return $next($request);
        }

        // Logged at warning with the observed address, because the two causes
        // need opposite responses: a scanner is routine, and Paystack having
        // added an address is a one-line config fix. Both look identical here,
        // which is exactly why the address is in the log.
        Log::warning('Paystack webhook rejected — source is not an allowlisted address', [
            'source_ip' => $sourceIp,
            'direct_peer' => $request->ip(),
            'forwarded_for' => $request->header('x-forwarded-for'),
        ]);

        return response()->json(['status' => 'forbidden source'], 403);
    }

    /**
     * The address to test against the allowlist.
     *
     * `$request->ip()` already resolves the client address through trusted
     * proxies, and that resolution is the whole difficulty here. With no trusted
     * proxies configured it returns the direct peer — which behind Cloudflare or
     * a load balancer is the CDN, not Paystack, and every genuine webhook would
     * be rejected. Conversely, blindly reading X-Forwarded-For is worse: that
     * header is attacker-controlled, so trusting it unconditionally would let
     * anyone spoof their way past the allowlist.
     *
     * So this trusts the framework's resolution and does not second-guess it.
     * Configuring trustProxies is therefore a prerequisite for this filter
     * working behind a CDN, and the log line above is what makes the gap
     * visible when it has not been done.
     */
    protected function resolveSourceIp(Request $request): string
    {
        return (string) $request->ip();
    }
}
