<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * Refuses to run database-destroying Artisan commands outside development.
 *
 * `migrate:fresh`, `migrate:reset` and `db:wipe` drop every table and rebuild
 * from migrations. That is a normal thing to reach for on a laptop and a
 * catastrophic thing to reach for on a box holding the live site, and the two
 * are hard to tell apart from the command line: same terminal, same command,
 * only the .env differs. Development here historically ran against the
 * production-named database, so the mistake was one `cd` away from being real.
 *
 * `local` and `testing` are allowed through — that is what makes
 * `migrate:fresh` usable at all. Everything else is refused, with an override
 * for the genuine emergency in ALLOW_DESTRUCTIVE_COMMANDS.
 */
class ProductionSafetyServiceProvider extends ServiceProvider
{
    /**
     * Commands that drop every table.
     *
     * Matched on the name as typed, so `migrate:fresh` and the aliased
     * `migrate:refresh` are both covered.
     *
     * @var list<string>
     */
    private const DESTRUCTIVE = [
        'migrate:fresh',
        'migrate:reset',
        'migrate:refresh',
        'db:wipe',
        'db:clear',
    ];

    public function boot(): void
    {
        if (! $this->isGuarded()) {
            return;
        }

        $this->app['events']->listen(CommandStarting::class, function (CommandStarting $event): void {
            $name = $this->commandName($event);

            if ($name === null || ! in_array($name, self::DESTRUCTIVE, true)) {
                return;
            }

            // The escape hatch, for a real recovery. Deliberately not a flag:
            // it has to be set in the environment that is about to lose its
            // data, not appended to the command that is doing it. Read from
            // config so it still works behind `config:cache`.
            if ((bool) config('safety.allow_destructive_commands')) {
                Log::warning('Destructive command allowed by ALLOW_DESTRUCTIVE_COMMANDS.', [
                    'command' => $name,
                    'environment' => $this->app->environment(),
                ]);

                return;
            }

            Log::warning('Blocked destructive command outside development.', [
                'command' => $name,
                'environment' => $this->app->environment(),
            ]);

            $this->fail($name);
        });
    }

    /**
     * Whether this environment needs guarding at all.
     *
     * `local` and `testing` are the two places these commands are legitimate,
     * and allowing `testing` explicitly keeps the suite from having to
     * impersonate production to test this guard.
     */
    private function isGuarded(): bool
    {
        return ! $this->app->environment(['local', 'testing']);
    }

    /**
     * The command as typed, or null when it cannot be determined.
     */
    private function commandName(CommandStarting $event): ?string
    {
        $name = trim((string) $event->command);

        return $name === '' ? null : $name;
    }

    private function fail(string $command): never
    {
        throw new \RuntimeException(sprintf(
            'Refusing to run "%s" in the "%s" environment. '
            .'It drops every table, and this environment is not development. '
            .'Run "php artisan migrate" to apply pending migrations. '
            .'If you genuinely need to wipe this database, set ALLOW_DESTRUCTIVE_COMMANDS=true '
            .'for this one command and make a backup first.',
            $command,
            $this->app->environment(),
        ));
    }
}