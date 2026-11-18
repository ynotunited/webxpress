<?php

namespace Tests\Feature\Admin;

use Illuminate\Console\Events\CommandStarting;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\TestCase;

/**
 * Destructive commands are refused outside development.
 *
 * This guards the mistake that took out the database once already: running
 * `migrate:fresh` on a machine whose .env pointed at the production-named
 * database. Nothing about the command line differs between the safe and the
 * dangerous case, so the check has to come from the environment.
 *
 * The guard is exercised by firing CommandStarting with a command name rather
 * than by shelling out, which keeps the suite fast and cannot damage whatever
 * database the suite is pointed at.
 */
class ProductionSafetyTest extends TestCase
{
    /**
     * Put the application into an environment and re-register the provider so
     * it evaluates the guard against that environment, exactly as it would on
     * a fresh process.
     */
    private function bootInEnvironment(string $environment): void
    {
        $this->app['env'] = $environment;
        $this->app->detectEnvironment(fn () => $environment);

        (new \App\Providers\ProductionSafetyServiceProvider($this->app))->boot();
    }

    private function attempt(string $command): ?RuntimeException
    {
        try {
            $this->app['events']->dispatch(new CommandStarting(
                $command,
                $this->consoleInput(),
                $this->consoleOutput(),
            ));
        } catch (RuntimeException $e) {
            return $e;
        }

        return null;
    }

    private function consoleInput(): InputInterface
    {
        return new ArrayInput([]);
    }

    private function consoleOutput(): OutputInterface
    {
        return new BufferedOutput();
    }

    public function test_migrate_fresh_is_refused_in_production(): void
    {
        $this->bootInEnvironment('production');

        $failure = $this->attempt('migrate:fresh');

        $this->assertNotNull($failure, 'migrate:fresh ran unguarded in production');
        $this->assertStringContainsString('Refusing to run "migrate:fresh"', $failure->getMessage());
    }

    public function test_every_destructive_command_is_refused_in_production(): void
    {
        $this->bootInEnvironment('production');

        foreach (['migrate:fresh', 'migrate:reset', 'migrate:refresh', 'db:wipe', 'db:clear'] as $command) {
            $this->assertNotNull(
                $this->attempt($command),
                "{$command} ran unguarded in production"
            );
        }
    }

    public function test_staging_is_guarded_too(): void
    {
        $this->bootInEnvironment('staging');

        $this->assertNotNull($this->attempt('migrate:fresh'));
    }

    public function test_ordinary_migrations_are_never_blocked(): void
    {
        $this->bootInEnvironment('production');

        // The guard must not make the safe command unusable, or it gets worked
        // around and stops protecting anything.
        $this->assertNull($this->attempt('migrate'));
        $this->assertNull($this->attempt('migrate:status'));
        $this->assertNull($this->attempt('db:seed'));
    }

    public function test_local_may_wipe_its_own_database(): void
    {
        $this->bootInEnvironment('local');

        // This is the whole reason the guard allows local: migrate:fresh is a
        // normal part of development.
        $this->assertNull($this->attempt('migrate:fresh'));
    }

    public function test_the_override_lifts_the_guard_for_one_command(): void
    {
        $this->bootInEnvironment('production');
        config(['safety.allow_destructive_commands' => true]);

        // For a real recovery — restoring a dump, or rebuilding a table whose
        // migrations are already wrong. The log warning it emits is what makes
        // this noticeable after the fact.
        $this->assertNull($this->attempt('migrate:fresh'));
    }

    public function test_the_override_is_read_from_config_so_config_cache_cannot_break_it(): void
    {
        // config:cache is how a deployed app loads configuration, and env()
        // returns null once values are baked into a cache file. An override
        // read straight from env() would silently stop working in production,
        // which is the only place it is ever needed.
        $this->assertTrue(
            array_key_exists('allow_destructive_commands', config('safety')),
            'safety.allow_destructive_commands is missing from config/safety.php'
        );
    }
}