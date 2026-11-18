<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Provisions the panel admin from environment configuration so that a fresh
 * deploy is never locked out of /admin.
 *
 * Configure in .env:
 *   ADMIN_NAME=Webxpress Admin
 *   ADMIN_EMAIL=admin@example.com
 *   ADMIN_PASSWORD=<at least 12 characters>
 *
 * The password is never committed. Idempotent: re-running updates the
 * existing admin rather than colliding on the unique email.
 */
class AdminUserSeeder extends Seeder
{
    private const MIN_PASSWORD_LENGTH = 12;

    public function run(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');

        // Unconfigured environments (fresh clone, CI) seed the rest of the
        // site normally and simply have no admin yet.
        if (blank($email)) {
            $this->command?->warn('AdminUserSeeder: ADMIN_EMAIL not set — skipping admin user.');

            return;
        }

        if (blank($password)) {
            throw new RuntimeException(
                'ADMIN_EMAIL is set but ADMIN_PASSWORD is missing. Set both, or remove ADMIN_EMAIL to skip the admin user.'
            );
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new RuntimeException(sprintf(
                'ADMIN_PASSWORD must be at least %d characters (got %d).',
                self::MIN_PASSWORD_LENGTH,
                strlen($password)
            ));
        }

        $admin = User::query()->firstOrNew(['email' => $email]);

        $admin->name = config('admin.name') ?: 'Admin';
        $admin->password = $password;
        $admin->email_verified_at = now();
        $admin->save();
    }
}
