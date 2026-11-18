<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmail, Notifiable;

    /**
     * Panel access.
     *
     * Without this contract Filament refuses panel access in any environment
     * other than `local`, so the admin would 403 on every real deploy.
     *
     * Access is restricted to the address in ADMIN_EMAIL — the same account
     * AdminUserSeeder provisions.
     *
     * This fails CLOSED. It used to return true whenever ADMIN_EMAIL was unset,
     * on the reasoning that an unconfigured local checkout should not be locked
     * out of its own panel. That is the wrong default: ADMIN_EMAIL is unset far
     * more often by accident than on purpose, and the cost of guessing wrong is
     * the entire admin panel — every page, post, review, deal and payment —
     * open to any account that can authenticate, including the test@example.com
     * user that factories create. The local escape hatch is to set ADMIN_EMAIL,
     * not to leave the gate open.
     *
     * There is deliberately no `app()->environment('local')` bypass. This
     * checkout's own .env ships APP_ENV=local next to the production APP_URL, so
     * an environment check would have handed production an open panel whenever
     * that file was deployed as-is.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        $adminEmail = trim((string) config('admin.email'));

        if ($adminEmail === '') {
            return false;
        }

        return hash_equals(strtolower($adminEmail), strtolower((string) $this->email));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
