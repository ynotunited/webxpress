<?php

namespace App\Providers\Filament;

use App\Filament\Auth\AdminLogin;
use App\Filament\Auth\AdminRequestPasswordReset;
use App\Filament\AvatarProviders\InitialsAvatarProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            // ADMIN_PATH, not a literal. The sanitised segment is the single
            // definition the CMS catch-all and the admin emails also read, so
            // moving the panel cannot quietly desynchronise them.
            ->path(admin_path())
            ->login(AdminLogin::class)
            // Filament defaults to ui-avatars.com, which the admin CSP blocks and
            // which would leak the admin name to a third party. Draw initials
            // locally as a data: SVG instead; img-src already allows data:.
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            // Self-service recovery and verification:
            //
            //   - passwordReset() mints tokens through Laravel's broker, which
            //     expires them after config('auth.passwords.users.expire') (60
            //     minutes) and rate limits both the request and the reset. The
            //     request page is overridden to refuse to answer whether an
            //     address is registered - see AdminRequestPasswordReset.
            //   - emailVerification() requires a signed-in admin whose
            //     email_verified_at is unset to verify before any panel page
            //     loads, instead of silently letting an unverified account use
            //     the panel. AdminUserSeeder provisions the account already
            //     verified, because the deployer who sets ADMIN_EMAIL is by
            //     definition vouching for it; the control matters when an
            //     account exists that no deployer vouched for.
            //   - emailChangeVerification() means changing the login address in
            //     the profile does not take effect until the new mailbox is
            //     verified, so an attacker who gets this far cannot silently
            //     retarget the recovery address to an inbox they control.
            ->passwordReset(AdminRequestPasswordReset::class)
            ->emailVerification()
            ->emailChangeVerification()
            ->profile()
            ->darkMode(false)
            ->colors([
                'primary' => '#E31937',
                'secondary' => '#1B2D5F',
            ])
            ->brandName('Webxpress')
            ->brandLogo(fn () => brand_logo('colored'))
            ->brandLogoHeight('2.25rem')
            ->favicon(fn () => brand_favicon())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
