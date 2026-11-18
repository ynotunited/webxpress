<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Destructive Command Escape Hatch
    |--------------------------------------------------------------------------
    |
    | ProductionSafetyServiceProvider refuses `migrate:fresh`, `db:wipe` and
    | friends in any environment that is not local or testing. This flag lifts
    | that refusal for a single invocation.
    |
    | It exists for a real recovery — restoring a dump into a broken database,
    | or rebuilding a table whose migrations are already wrong. Set it for one
    | command, not in the deployed .env, and take a backup first:
    |
    |     ALLOW_DESTRUCTIVE_COMMANDS=true php artisan migrate:fresh
    |
    | Read from config rather than env() so it survives `config:cache`, which
    | is how a deployed application loads configuration: an env() call returns
    | null once the values are baked into a cache file, and the escape hatch
    | would silently stop working exactly when it was needed.
    |
    */

    'allow_destructive_commands' => (bool) env('ALLOW_DESTRUCTIVE_COMMANDS', false),

];