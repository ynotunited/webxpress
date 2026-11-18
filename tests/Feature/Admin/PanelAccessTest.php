<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Panel access and admin provisioning.
 *
 * Without the FilamentUser contract Filament 403s every panel request
 * outside the `local` environment, so these tests guard both halves of the
 * gate: the panel is reachable, and only by the configured admin.
 */
class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_email_lock_permits_the_configured_admin(): void
    {
        config(['admin.email' => 'boss@webxpresstech.com.ng']);

        $admin = User::factory()->create(['email' => 'boss@webxpresstech.com.ng']);

        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_admin_email_lock_rejects_other_users(): void
    {
        config(['admin.email' => 'boss@webxpresstech.com.ng']);

        $other = User::factory()->create(['email' => 'intern@webxpresstech.com.ng']);

        $this->assertFalse($other->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_admin_email_lock_is_case_insensitive(): void
    {
        config(['admin.email' => 'Boss@WebxpressTech.com.ng']);

        $admin = User::factory()->create(['email' => 'boss@webxpresstech.com.ng']);

        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_panel_is_closed_when_no_admin_email_is_configured(): void
    {
        config(['admin.email' => '']);

        $any = User::factory()->create(['email' => 'anyone@example.com']);

        // Fails closed on purpose. An unset ADMIN_EMAIL is far more likely to be
        // an accident on a deploy than a deliberate local choice, and "accident"
        // used to mean every authenticated account could reach every page, post,
        // review, deal and payment in the panel.
        $this->assertFalse($any->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_unconfigured_admin_blocks_the_panel_over_http(): void
    {
        config(['admin.email' => '']);

        $any = User::factory()->create(['email' => 'anyone@example.com']);

        $this->actingAs($any)->get('/'.admin_path())->assertForbidden();
    }

    public function test_non_admin_user_is_denied_the_panel_over_http(): void
    {
        config(['admin.email' => 'boss@webxpresstech.com.ng']);

        $intern = User::factory()->create(['email' => 'intern@webxpresstech.com.ng']);

        $this->actingAs($intern)->get('/'.admin_path())->assertForbidden();
    }

    public function test_configured_admin_reaches_the_panel_over_http(): void
    {
        config(['admin.email' => 'boss@webxpresstech.com.ng']);

        $admin = User::factory()->create(['email' => 'boss@webxpresstech.com.ng']);

        $this->actingAs($admin)->get('/'.admin_path())->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/'.admin_path())->assertRedirect();
    }

    // ------------------------------------------------------------ seeder

    public function test_admin_seeder_skips_when_no_email_is_configured(): void
    {
        config(['admin.email' => '', 'admin.password' => '']);

        (new AdminUserSeeder)->run();

        $this->assertSame(0, User::where('email', 'test@example.com')->count());
    }

    public function test_admin_seeder_creates_the_admin_from_config(): void
    {
        config(['admin.email' => 'boss@webxpresstech.com.ng', 'admin.password' => 'a-long-enough-password']);

        (new AdminUserSeeder)->run();

        $admin = User::where('email', 'boss@webxpresstech.com.ng')->first();

        $this->assertNotNull($admin);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('a-long-enough-password', $admin->password));
    }

    public function test_admin_seeder_is_idempotent(): void
    {
        config(['admin.email' => 'boss@webxpresstech.com.ng', 'admin.password' => 'a-long-enough-password']);

        (new AdminUserSeeder)->run();
        (new AdminUserSeeder)->run();

        $this->assertSame(1, User::where('email', 'boss@webxpresstech.com.ng')->count());
    }

    public function test_admin_seeder_throws_when_password_is_missing(): void
    {
        config(['admin.email' => 'boss@webxpresstech.com.ng', 'admin.password' => '']);

        $this->expectException(\RuntimeException::class);

        (new AdminUserSeeder)->run();
    }

    public function test_admin_seeder_rejects_a_short_password(): void
    {
        config(['admin.email' => 'boss@webxpresstech.com.ng', 'admin.password' => 'short']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('at least 12 characters');

        (new AdminUserSeeder)->run();
    }
}
