<?php

namespace App\Filament\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Admin sign-in, throttled against the account as well as the address.
 *
 * Filament's own login page already refuses a sixth attempt from one IP inside a
 * minute, which is the right default. But that limiter is keyed on the IP alone:
 *
 *     'livewire-rate-limiter:' . sha1($component . '|' . $method . '|' . request()->ip())
 *
 * An attacker who rotates source addresses gets a fresh allowance every time, so
 * against a panel with a single account the cap never binds — the limit only makes
 * a distributed attempt slower, and slower is still enough when there are no
 * other controls on how often a password can be tried.
 *
 * This adds a second limiter keyed on the account being targeted, so the number of
 * guesses anyone can make against one admin identity is capped no matter where the
 * attempts appear to come from. It runs in addition to Filament's, never instead.
 *
 * The deliberate cost is a lockout an attacker can trigger: hammering the admin
 * address from anywhere locks the real admin out for `admin.login_decay_seconds`.
 * That is the right way round — an account under sustained guessing is a fact worth
 * responding to, and `RecordAdminAuthentication` writes every failure to the audit
 * trail regardless. If the window proves too tight, widen the decay in .env rather
 * than removing the limit.
 */
class AdminLogin extends Login
{
    public function authenticate(): ?LoginResponse
    {
        $email = $this->targetAccount();

        if ($email === null) {
            // Nothing to key a limit on. Hand it to the form, which rejects it
            // with a validation message — an unparseable address is not an
            // attempt against a real account, and treating it as one would let
            // junk input fill the limiter and lock the admin out for nothing.
            return parent::authenticate();
        }

        $key = $this->accountKey($email);
        $maxAttempts = max(1, (int) config('admin.login_max_attempts', 5));
        $decay = max(1, (int) config('admin.login_decay_seconds', 300));

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            $this->getRateLimitedNotification(new TooManyRequestsException(
                component: static::class,
                method: 'authenticate',
                ip: (string) request()->ip(),
                secondsUntilAvailable: $seconds,
            ))?->send();

            Log::warning('Admin sign-in refused: the target account is locked out.', [
                'account' => $email,
                'ip' => request()->ip(),
                'locked_for_seconds' => $seconds,
                'attempts_over' => $maxAttempts,
            ]);

            return null;
        }

        RateLimiter::hit($key, $decay);

        $response = parent::authenticate();

        // parent() returns null both for a wrong password and when its own IP
        // limiter trips, so the only reliable signal that these credentials were
        // accepted is a response object. Clearing on the wrong signal would hand
        // an unlimited number of guesses to anyone who triggers the IP limit.
        if ($response !== null) {
            RateLimiter::clear($key);
        }

        return $response;
    }

    /**
     * The address being signed in to, if it is one.
     *
     * Read straight off the form state rather than through getState(), which
     * would validate and throw before the limiter ever ran — the limit has to be
     * able to see an attempt that the form is going to reject.
     */
    private function targetAccount(): ?string
    {
        $email = $this->data['email'] ?? null;

        if (! is_string($email)) {
            return null;
        }

        $email = trim($email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }

    private function accountKey(string $email): string
    {
        // Lower-cased so that varying the casing of one address cannot be used to
        // buy a second allowance for the same account.
        return 'admin-login:'.sha1(mb_strtolower($email));
    }
}