<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Notifications\Notification;
use Illuminate\Auth\Passwords\PasswordBroker;

/**
 * The password reset request page, hardened against address enumeration.
 *
 * Laravel's broker answers an unknown address with passwords.user ("we can't
 * find a user with that email"), and Filament renders that as a danger
 * notification. On a single-admin panel that turns the recovery page into a
 * clean probe for "is this the admin's address?": try addresses, the ones that
 * error are not the admin's.
 *
 * Knowing the admin's address isn't a password in itself, but it is the first
 * fact any targeted attack on this panel needs, and the two paths can be made
 * identical for the price of one override. An unknown address now gets exactly
 * the message a real request produces; the legitimate admin loses a precise
 * error and gains nothing.
 *
 * The broker's own throttle is treated the same way. A second request for a
 * registered address inside the throttle window returns RESET_THROTTLED, which
 * the parent renders as "please wait before retrying"; an unknown address keeps
 * returning the success message. Asking twice therefore reopens the same gap,
 * just one request later, so the throttled answer is folded into the success
 * message too. It still refuses to send a second link - only the answer is
 * generic.
 *
 * The request is additionally rate limited by the parent — 2 per minute per
 * address by IP — and that limiter fires before the broker, so it applies to
 * registered and unknown addresses alike and does not reopen the gap.
 */
class AdminRequestPasswordReset extends RequestPasswordReset
{
    protected function getFailureNotification(string $status): ?Notification
    {
        if (in_array($status, [PasswordBroker::INVALID_USER, PasswordBroker::RESET_THROTTLED], true)) {
            return Notification::make()
                ->title(__('passwords.sent'))
                ->body(__('filament-panels::auth/pages/password-reset/request-password-reset.notifications.sent.body'))
                ->success();
        }

        return parent::getFailureNotification($status);
    }
}
