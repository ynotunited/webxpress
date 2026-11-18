<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Panel Administrator
    |--------------------------------------------------------------------------
    |
    | Credentials used by AdminUserSeeder to provision the admin user on a fresh
    | deploy. Leave ADMIN_EMAIL empty to skip creating an admin.
    | Never commit a real password — keep these in your .env only.
    |
    */

    'name' => env('ADMIN_NAME', 'Webxpress Admin'),

    'email' => env('ADMIN_EMAIL', ''),

    'password' => env('ADMIN_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Panel Path
    |--------------------------------------------------------------------------
    |
    | The URL segment the admin panel answers on. Change it to something a scanner
    | cannot guess — `php artisan tinker` will mint one:
    |
    |     echo 'panel-'.Str::random(24);
    |
    | Be clear about what this buys you. A hidden path stops an unauthenticated
    | scanner from finding the login form at all, which removes the cheap target
    | and most of the noise from the logs. It is not a security control on its own:
    | the path leaks from a bookmark, a Referer header, a screenshot, or anyone with
    | read access to the repository. The controls that actually hold are the
    | fail-closed canAccessPanel() check, the login throttling below, and a password
    | that is not guessable.
    |
    | Changing it is safe — the panel follows this value, and the CMS catch-all
    | reserves the segment so a page can never be created on top of it. Anything
    | that links to the panel (the admin notification emails) reads it from here too,
    | so those links follow automatically.
    |
    | Sanitised on boot: anything outside [a-z0-9_-] is stripped and the result is
    | lowercased, so a stray slash or dot cannot turn this into a nested path.
    |
    */

    'path' => env('ADMIN_PATH', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Login Throttling
    |--------------------------------------------------------------------------
    |
    | Filament already refuses a sixth sign-in attempt from one IP address within a
    | minute. That limit is keyed on the IP alone, so an attacker who rotates source
    | addresses gets a fresh allowance every time and the cap never binds — and
    | `throttle` only slows a distributed attempt, it does not stop it.
    |
    | These limits are keyed on the account being targeted instead, so the number of
    | guesses anyone can make against one admin account is capped no matter where
    | they appear to come from. They apply on top of Filament's, never instead.
    |
    | The tradeoff is a lockout an attacker can trigger on purpose: hammering
    | ADMIN_EMAIL from anywhere locks the real admin out for `decay_seconds`. That
    | is the intended direction — an account being guessed at is a fact worth
    | acting on, and the audit trail records every failure. Widen `decay_seconds`
    | rather than removing the limit if it proves too tight.
    |
    */

    'login_max_attempts' => (int) env('ADMIN_LOGIN_MAX_ATTEMPTS', 5),

    'login_decay_seconds' => (int) env('ADMIN_LOGIN_DECAY_SECONDS', 300),

];