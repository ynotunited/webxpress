<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

/**
 * Records admin sign-in attempts.
 *
 * Bound to the framework's own Login/Failed events rather than Filament's login
 * page, so the trail covers every path into the panel without depending on that
 * page's internals.
 *
 * This is the whole of the application's authentication. The client deal portal
 * is secured by an unguessable token in the URL rather than a session, so any
 * Login event reaching this listener is an admin sign-in.
 */
class RecordAdminAuthentication
{
    public function handleLogin(Login $event): void
    {
        $this->record(
            'logged_in',
            $event->user->email,
            $event->user->getKey(),
            sprintf('Admin signed in: %s', $event->user->email)
        );
    }

    public function handleFailed(Failed $event): void
    {
        // The attempted password is in $event->credentials and is deliberately
        // not touched here. Writing it anywhere would turn this table into a
        // password history — including for the successful attempts where the
        // user simply typed it wrong.
        $this->record('login_failed', null, null, sprintf(
            'Failed admin sign-in for: %s',
            (string) ($event->credentials['email'] ?? 'unknown')
        ));
    }

    private function record(string $event, ?string $email, int|string|null $userId, string $summary): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'user_email' => $email,
            'event' => $event,
            'subject_type' => null,
            'subject_id' => null,
            'summary' => $summary,
            'changes' => null,
            'ip_address' => Request::ip(),
            'user_agent' => mb_substr((string) Request::userAgent(), 0, 255),
        ]);
    }
}