<?php

namespace Tests\Feature\Admin;

use App\Filament\Auth\AdminLogin;
use App\Filament\Auth\AdminRequestPasswordReset;
use App\Models\User;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Self-service recovery for the single-admin panel.
 *
 * The panel has exactly one account, so "I can reset the admin's password" is
 * the difference between a locked owner and a locked-out site. These tests walk
 * the broker end to end: the login page offers the link, the request page mints
 * a hashed token and mails a signed URL, the URL loads, and the token swaps the
 * password exactly once for an unexpired, correctly signed request.
 *
 * They also pin the anti-enumeration override on the request page. On a panel
 * with one account the recovery form is otherwise a clean oracle for "is this
 * the admin's address?", which is the first fact a targeted attack wants.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_EMAIL = 'boss@webxpresstech.com.ng';

    private const OLD_PASSWORD = 'the-old-admin-password';

    private const NEW_PASSWORD = 'the-brand-new-admin-password-2026';

    protected function setUp(): void
    {
        parent::setUp();

        config(['admin.email' => self::ADMIN_EMAIL]);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'email' => self::ADMIN_EMAIL,
            'password' => Hash::make(self::OLD_PASSWORD),
        ]);
    }

    private function submitResetRequest(string $email): void
    {
        Livewire::test(AdminRequestPasswordReset::class)
            ->fillForm(['email' => $email])
            ->call('request');
    }

    /**
     * Drive the request form and return the URL the notification carried.
     */
    private function requestResetLink(User $user): string
    {
        Notification::fake();

        $this->submitResetRequest($user->email);

        $sent = Notification::sent($user, ResetPasswordNotification::class);

        $this->assertCount(1, $sent, 'the reset notification was not sent to the admin');

        $url = $sent->first()->url;

        $this->assertIsString($url);

        return $url;
    }

    private function tokenFrom(string $url): string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertArrayHasKey('token', $query, 'the reset URL did not carry a token');

        return (string) $query['token'];
    }

    /**
     * Submit the request form and return the notification the page answered with.
     *
     * The answer is captured from the session the page pushed it to rather than
     * matched against a fixed shape, because the point is to compare a known
     * address's answer with an unknown one, not to pin the wording. The random
     * per-notification id is dropped so two structurally identical answers
     * compare equal.
     *
     * Filament moves a notification out of `filament.notifications` and into
     * `filament.claimed_notifications` as the Livewire request finishes, so the
     * answer is read from where it lands rather than where it was queued.
     *
     * The page allows two requests per address per minute by IP; that limiter is
     * cleared first so each call measures what the broker answers, not how many
     * times the page has already been asked.
     */
    private function answerFor(string $email): ?array
    {
        Cache::flush();

        $this->submitResetRequest($email);

        $notifications = session('filament.claimed_notifications', [])
            ?: session('filament.notifications', []);
        $answer = $notifications ? end($notifications) : null;

        if ($answer !== null) {
            unset($answer['id']);
        }

        return $answer;
    }

    public function test_the_login_page_links_to_the_reset_request_page(): void
    {
        $this->get('/'.admin_path().'/login')
            ->assertOk()
            ->assertSee(admin_path().'/password-reset/request', false);
    }

    public function test_a_reset_request_stores_a_hashed_token_and_mails_a_signed_panel_url(): void
    {
        $admin = $this->makeAdmin();

        $url = $this->requestResetLink($admin);

        // The link points into this panel's reset page and is signed, so a token
        // scraped from a leak is not enough without the signature.
        $path = (string) parse_url($url, PHP_URL_PATH);
        $this->assertStringStartsWith('/'.admin_path().'/password-reset/', $path);
        $this->assertStringEndsWith('/reset', $path);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(self::ADMIN_EMAIL, $query['email'] ?? null);
        $this->assertArrayHasKey('signature', $query);

        $token = $this->tokenFrom($url);

        // The broker stores a bcrypt hash, never the token itself: a database
        // read must not yield a working link.
        $row = DB::table('password_reset_tokens')->where('email', self::ADMIN_EMAIL)->first();

        $this->assertNotNull($row, 'the broker did not persist a reset token');
        $this->assertNotSame($token, $row->token);
        $this->assertTrue(Hash::check($token, $row->token), 'the stored token is not the hash of the mailed token');
    }

    public function test_the_signed_link_loads_and_a_tampered_one_does_not(): void
    {
        $admin = $this->makeAdmin();
        $url = $this->requestResetLink($admin);

        $this->get($url)->assertOk();

        $token = $this->tokenFrom($url);
        $path = (string) parse_url($url, PHP_URL_PATH);

        // Swapping the address the link recovers must invalidate the signature.
        // It covers the email, so an attacker who intercepts a link cannot
        // retarget it at an inbox they control without the app's key.
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $query['email'] = 'attacker@elsewhere.test';

        $this->get($path.'?'.http_build_query($query))
            ->assertForbidden();

        // And the token alone, with no signature at all, is refused too.
        $this->get($path.'?'.http_build_query([
            'email' => self::ADMIN_EMAIL,
            'token' => $token,
        ]))->assertForbidden();
    }

    public function test_an_unknown_address_gets_the_answer_the_admin_gets(): void
    {
        $admin = $this->makeAdmin();

        Notification::fake();

        $adminAnswer = $this->answerFor(self::ADMIN_EMAIL);
        $unknownAnswer = $this->answerFor('not-the-admin@elsewhere.test');

        $this->assertNotNull($adminAnswer, 'the request page answered with nothing');
        $this->assertSame($adminAnswer, $unknownAnswer, 'the request page tells an unknown address apart from the admin');

        // The identical answer is only honest if the unknown address gets no
        // mail: the message is the same, the work behind it is not.
        Notification::assertSentTo($admin, ResetPasswordNotification::class);
        Notification::assertSentToTimes($admin, ResetPasswordNotification::class, 1);
    }

    public function test_a_registered_address_outside_the_panel_is_treated_as_unknown(): void
    {
        $admin = $this->makeAdmin();
        $stranger = User::factory()->create(['email' => 'intern@webxpresstech.com.ng']);

        Notification::fake();

        $strangerAnswer = $this->answerFor($stranger->email);

        // The broker mints a token for any user it finds, but the callback skips
        // delivery for anyone who cannot reach the panel, so the stranger never
        // receives a usable recovery link.
        Notification::assertNothingSent();

        $adminAnswer = $this->answerFor(self::ADMIN_EMAIL);

        Notification::assertSentTo($admin, ResetPasswordNotification::class);

        $this->assertSame($adminAnswer, $strangerAnswer, 'a registered non-panel account is distinguishable from the admin');
    }

    public function test_repeating_a_probe_does_not_reveal_whether_the_address_is_registered(): void
    {
        $admin = $this->makeAdmin();

        Notification::fake();

        // Laravel's broker throttles a second link for the same address inside a
        // minute. If that answer differs from the unknown address's, a prober
        // learns the address is real simply by asking twice - no need to receive
        // anything.
        $knownFirst = $this->answerFor(self::ADMIN_EMAIL);
        $knownSecond = $this->answerFor(self::ADMIN_EMAIL);
        $unknownFirst = $this->answerFor('nobody@elsewhere.test');
        $unknownSecond = $this->answerFor('nobody@elsewhere.test');

        $this->assertSame($knownFirst, $unknownFirst);
        $this->assertSame($knownSecond, $unknownSecond, 'a repeated probe tells a registered address apart from an unknown one');

        // Still only one link, however many times the form is submitted.
        Notification::assertSentToTimes($admin, ResetPasswordNotification::class, 1);
    }

    public function test_a_valid_token_sets_a_new_password_and_retires_the_old_one(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFrom($this->requestResetLink($admin));

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => self::ADMIN_EMAIL])
            ->fillForm([
                'password' => self::NEW_PASSWORD,
                'passwordConfirmation' => self::NEW_PASSWORD,
            ])
            ->call('resetPassword')
            ->assertNotified();

        $admin->refresh();

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $admin->password));
        $this->assertFalse(Hash::check(self::OLD_PASSWORD, $admin->password));

        // The token is spent: it cannot be replayed.
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => self::ADMIN_EMAIL]);

        // And the new password is the one the panel actually accepts. Asserting on
        // authentication rather than the hash alone is what proves the reset did
        // not leave the account unusable.
        Livewire::test(AdminLogin::class)
            ->fillForm(['email' => self::ADMIN_EMAIL, 'password' => self::NEW_PASSWORD])
            ->call('authenticate');

        $this->assertTrue(auth()->check(), 'the panel did not accept the password the reset installed');
    }

    public function test_a_token_cannot_be_used_twice(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFrom($this->requestResetLink($admin));

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => self::ADMIN_EMAIL])
            ->fillForm(['password' => self::NEW_PASSWORD, 'passwordConfirmation' => self::NEW_PASSWORD])
            ->call('resetPassword')
            ->assertNotified();

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => self::ADMIN_EMAIL])
            ->fillForm(['password' => self::NEW_PASSWORD.'-again', 'passwordConfirmation' => self::NEW_PASSWORD.'-again'])
            ->call('resetPassword')
            ->assertNotified(__('passwords.token'));

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $admin->refresh()->password), 'a spent token changed the password a second time');
    }

    public function test_an_expired_token_is_refused(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->tokenFrom($this->requestResetLink($admin));

        // The broker expires a token after config('auth.passwords.users.expire')
        // minutes; a link found in an old mailbox must not still work.
        $this->travel(61)->minutes();

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => self::ADMIN_EMAIL])
            ->fillForm(['password' => self::NEW_PASSWORD, 'passwordConfirmation' => self::NEW_PASSWORD])
            ->call('resetPassword')
            ->assertNotified(__('passwords.token'));

        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $admin->refresh()->password), 'an expired token still changed the password');
    }
}
