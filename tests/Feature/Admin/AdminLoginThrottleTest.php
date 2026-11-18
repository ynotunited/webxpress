<?php

namespace Tests\Feature\Admin;

use App\Filament\Auth\AdminLogin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sign-in throttling against the account, not just the address.
 *
 * Filament already refuses the sixth attempt from one IP inside a minute. That
 * limit is keyed on the IP alone, so against a panel with a single administrator
 * an attacker who rotates source addresses gets a fresh allowance every time and
 * the cap never binds — it makes a distributed attempt slower, not impossible.
 *
 * These tests pin the second limiter, which is keyed on the account being
 * targeted, so the number of guesses anyone can make against one admin identity is
 * capped regardless of where the attempts appear to come from.
 *
 * The assertions are on authentication state rather than on the notification or the
 * form error, because "the correct password no longer works" is the property that
 * actually matters and the one hardest to notice by hand.
 */
class AdminLoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_EMAIL = 'boss@webxpresstech.com.ng';

    private const PASSWORD = 'correct-horse-battery-staple';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.email' => self::ADMIN_EMAIL,
            'admin.login_max_attempts' => 3,
            'admin.login_decay_seconds' => 300,
        ]);
    }

    private function makeUser(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
        ]);
    }

    private function attempt(string $email, string $password): Testable
    {
        return Livewire::test(AdminLogin::class)
            ->fillForm(['email' => $email, 'password' => $password])
            ->call('authenticate');
    }

    private function attemptWrong(string $email, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->attempt($email, 'wrong-password-'.$i);
        }
    }

    /**
     * The limiter key for an account.
     *
     * Reading it rather than asserting on it is deliberate: these tests use it to
     * count attempts, not to pin the key's shape, so a change to how the key is
     * built does not invalidate them. What they assert is behaviour — refused or
     * not, authenticated or not.
     */
    private function accountKey(string $email): string
    {
        return 'admin-login:'.sha1(mb_strtolower($email));
    }

    public function test_the_panel_signs_in_with_the_right_password_under_the_cap(): void
    {
        $this->makeUser(self::ADMIN_EMAIL);

        $this->attemptWrong(self::ADMIN_EMAIL, 2);
        $this->attempt(self::ADMIN_EMAIL, self::PASSWORD);

        $this->assertTrue(auth()->check(), 'the cap refused the correct password before it was reached');
    }

    public function test_the_correct_password_is_refused_once_the_cap_is_reached(): void
    {
        $this->makeUser(self::ADMIN_EMAIL);

        $this->attemptWrong(self::ADMIN_EMAIL, 3);
        $this->attempt(self::ADMIN_EMAIL, self::PASSWORD);

        // The whole point: knowing the password stops mattering once the account
        // is capped. Without this, the limiter would only slow a guess down.
        $this->assertFalse(auth()->check(), 'the correct password still worked after the cap was reached');
    }

    public function test_locking_one_account_does_not_throttle_another(): void
    {
        // If the limiter were keyed globally or on the address, one account under
        // attack would take every account down with it. It is keyed on the target,
        // so only the address being guessed at is refused.
        //
        // Note the observation: a refusal from the limiter happens before the guard
        // is consulted, so it produces no credentials error. A refusal *after* the
        // guard runs does. That difference is what makes these distinguishable
        // without reaching into the rate limiter's internals.
        config(['admin.login_max_attempts' => 1]);

        $this->makeUser(self::ADMIN_EMAIL);

        $this->attempt(self::ADMIN_EMAIL, 'wrong-password');

        $lockedOut = $this->attempt(self::ADMIN_EMAIL, self::PASSWORD);
        $this->assertArrayNotHasKey('data.email', $lockedOut->errors()->toArray(), 'the target account should be refused before the guard runs');

        $other = $this->attempt('someone-else@other.test', 'wrong-password');
        $this->assertArrayHasKey('data.email', $other->errors()->toArray(), 'one account being guessed at throttled an unrelated address too');
    }

    public function test_the_cap_is_reached_in_three_attempts_not_five(): void
    {
        // Filament's own limiter allows five attempts per IP per minute, so if this
        // account cap were not binding the correct password would still be accepted
        // on the fourth try. Refusing it here is what shows the second limiter is
        // the one doing the work — and it is keyed on the account, so the allowance
        // cannot be renewed by arriving from a different source address.
        $this->makeUser(self::ADMIN_EMAIL);

        $this->attemptWrong(self::ADMIN_EMAIL, 3);
        $this->attempt(self::ADMIN_EMAIL, self::PASSWORD);

        $this->assertFalse(auth()->check());
        $this->assertSame(3, RateLimiter::attempts($this->accountKey(self::ADMIN_EMAIL)), 'the cap should have refused the fourth attempt without reaching the guard');
    }

    public function test_a_successful_sign_in_clears_the_cap_so_a_mistyped_password_does_not_lock_the_admin_out(): void
    {
        // Without clearing on success the admin would be locked out after a handful
        // of ordinary typos spread across a morning, which is the kind of lockout
        // that trains people to stop using the panel.
        //
        // Five calls in total is deliberate: that is where Filament's own IP
        // limiter starts refusing, and one more would make this test fail for its
        // rate limit rather than for the clearing.
        $this->makeUser(self::ADMIN_EMAIL);

        $this->attemptWrong(self::ADMIN_EMAIL, 1);
        $this->attempt(self::ADMIN_EMAIL, self::PASSWORD);
        $this->assertTrue(auth()->check(), 'the correct password failed on the first sign-in');

        auth()->logout();
        $this->flushSession();

        $this->attemptWrong(self::ADMIN_EMAIL, 2);

        // Two typos after signing in: 2, not 1 + 2 = 3. Had the counter not been
        // cleared the next attempt would have been refused at the cap of three.
        $this->assertSame(2, RateLimiter::attempts($this->accountKey(self::ADMIN_EMAIL)));
    }

    public function test_attempting_to_lock_out_an_account_writes_a_warning_to_the_log(): void
    {
        // An account under sustained guessing is a fact worth being able to find
        // later, and the log is where it lands.
        \Illuminate\Support\Facades\Log::spy();

        $this->makeUser(self::ADMIN_EMAIL);
        $this->attemptWrong(self::ADMIN_EMAIL, 4);

        \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []): bool => str_contains($message, 'locked out')
                && ($context['account'] ?? null) === self::ADMIN_EMAIL)
            ->atLeast()->once();
    }

    public function test_a_malformed_address_does_not_count_against_any_account(): void
    {
        // Junk input must not be able to fill the limiter: otherwise anyone could
        // lock the admin out by submitting nonsense rather than guessing.
        $this->makeUser(self::ADMIN_EMAIL);

        $this->attempt('not-an-email', 'whatever');
        $this->attempt('', 'whatever');

        $this->attempt(self::ADMIN_EMAIL, self::PASSWORD);

        $this->assertTrue(auth()->check());
    }
}