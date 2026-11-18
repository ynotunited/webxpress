<?php

namespace Tests\Feature\Status;

use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * status:publish writes status/data/status.json — the only file the public
 * status page reads. These tests point it at a temp directory and assert the
 * file, because a command that writes the same state over and over (bumping
 * updated_at forever), or that accepts an impossible window, is the kind of
 * thing that makes the page silently stop being honest.
 */
class StatusPublishTest extends TestCase
{
    private string $dir;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'wx-status-'.uniqid('', true);
        mkdir($this->dir, 0777, true);
        $this->file = $this->dir.\DIRECTORY_SEPARATOR.'status.json';

        file_put_contents($this->file, json_encode([
            'name' => 'Test Status',
            'homepage' => 'https://example.com',
            'updated_at' => null,
            'overall' => 'operational',
            'monitors' => [],
            'incidents' => [],
            'maintenance' => [],
        ], JSON_PRETTY_PRINT));

        config(['status.path' => $this->dir]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.\DIRECTORY_SEPARATOR.'*') ?: [] as $entry) {
            @unlink($entry);
        }
        @rmdir($this->dir);

        parent::tearDown();
    }

    private function state(): array
    {
        return json_decode((string) file_get_contents($this->file), true);
    }

    public function test_a_monitor_is_registered(): void
    {
        $this->artisan('status:publish', [
            'action' => 'monitor',
            '--name' => 'Website',
            '--status' => 'up',
        ])->assertSuccessful();

        $data = $this->state();

        $this->assertSame('up', $data['monitors'][0]['status']);
        $this->assertSame('operational', $data['overall']);
        $this->assertNotNull($data['updated_at']);
    }

    public function test_a_down_monitor_flips_the_overall_status(): void
    {
        $this->artisan('status:publish', ['action' => 'monitor', '--name' => 'Website', '--status' => 'up'])->assertSuccessful();
        $this->artisan('status:publish', ['action' => 'monitor', '--name' => 'Website', '--status' => 'down'])->assertSuccessful();

        $this->assertSame('down', $this->state()['monitors'][0]['status']);
        $this->assertSame('down', $this->state()['overall']);
    }

    public function test_an_incident_opens_with_a_root_cause_update_and_degrades_the_page(): void
    {
        $this->artisan('status:publish', [
            'action' => 'incident',
            '--title' => 'Checkout errors',
            '--severity' => 'major',
            '--state' => 'investigating',
            '--message' => 'We are looking into it.',
        ])->assertSuccessful();

        $data = $this->state();
        $incident = $data['incidents'][0];

        $this->assertStringStartsWith('inc-', $incident['id']);
        $this->assertSame('Checkout errors', $incident['title']);
        $this->assertSame('major', $incident['severity']);
        $this->assertSame('investigating', $incident['status']);
        $this->assertNull($incident['resolved_at']);
        $this->assertCount(1, $incident['updates']);
        $this->assertSame('degraded', $data['overall']);
    }

    public function test_a_critical_incident_takes_the_page_down(): void
    {
        $this->artisan('status:publish', [
            'action' => 'incident',
            '--title' => 'Payments down',
            '--severity' => 'critical',
        ])->assertSuccessful();

        $this->assertSame('down', $this->state()['overall']);
    }

    public function test_an_incident_timeline_grows_with_each_update(): void
    {
        $this->artisan('status:publish', ['action' => 'incident', '--title' => 'Checkout errors', '--severity' => 'major'])->assertSuccessful();
        $id = $this->state()['incidents'][0]['id'];

        $this->artisan('status:publish', [
            'action' => 'incident',
            '--id' => $id,
            '--state' => 'identified',
            '--message' => 'Found it: provider timeout.',
        ])->assertSuccessful();

        $incident = $this->state()['incidents'][0];

        $this->assertSame('identified', $incident['status']);
        $this->assertCount(2, $incident['updates']);
        $this->assertSame('Found it: provider timeout.', $incident['updates'][1]['message']);
    }

    public function test_resolving_an_incident_returns_the_page_to_operational(): void
    {
        $this->artisan('status:publish', ['action' => 'incident', '--title' => 'Checkout errors', '--severity' => 'major'])->assertSuccessful();
        $id = $this->state()['incidents'][0]['id'];

        $this->artisan('status:publish', [
            'action' => 'incident',
            '--id' => $id,
            '--state' => 'resolved',
            '--message' => 'All clear.',
        ])->assertSuccessful();

        $data = $this->state();

        $this->assertSame('resolved', $data['incidents'][0]['status']);
        $this->assertNotNull($data['incidents'][0]['resolved_at']);
        $this->assertSame('operational', $data['overall']);
    }

    public function test_maintenance_is_scheduled_for_the_future(): void
    {
        $this->artisan('status:publish', [
            'action' => 'maintenance',
            '--title' => 'Server maintenance',
            '--starts' => Carbon::now()->addDay()->toDateTimeString(),
            '--ends' => Carbon::now()->addDay()->addHours(3)->toDateTimeString(),
            '--message' => 'Brief blips possible.',
        ])->assertSuccessful();

        $window = $this->state()['maintenance'][0];

        $this->assertStringStartsWith('mnt-', $window['id']);
        $this->assertSame('Server maintenance', $window['title']);
        $this->assertFalse($window['cancelled']);
        $this->assertSame('operational', $this->state()['overall']);
    }

    public function test_an_active_maintenance_window_flips_the_overall_status(): void
    {
        $this->artisan('status:publish', [
            'action' => 'maintenance',
            '--title' => 'Active window',
            '--starts' => Carbon::now()->subHour()->toDateTimeString(),
            '--ends' => Carbon::now()->addHour()->toDateTimeString(),
        ])->assertSuccessful();

        $this->assertSame('maintenance', $this->state()['overall']);
    }

    public function test_maintenance_can_be_cancelled(): void
    {
        $this->artisan('status:publish', [
            'action' => 'maintenance',
            '--title' => 'Server maintenance',
            '--starts' => Carbon::now()->addDay()->toDateTimeString(),
            '--ends' => Carbon::now()->addDay()->addHours(3)->toDateTimeString(),
        ])->assertSuccessful();
        $id = $this->state()['maintenance'][0]['id'];

        $this->artisan('status:publish', ['action' => 'maintenance', '--id' => $id, '--cancel' => true])->assertSuccessful();

        $this->assertTrue($this->state()['maintenance'][0]['cancelled']);
    }

    public function test_a_maintenance_window_cannot_end_before_it_starts(): void
    {
        $this->artisan('status:publish', [
            'action' => 'maintenance',
            '--title' => 'Impossible window',
            '--starts' => Carbon::now()->addDay()->toDateTimeString(),
            '--ends' => Carbon::now()->toDateTimeString(),
        ])->assertFailed();

        $this->assertSame([], $this->state()['maintenance']);
    }

    public function test_an_unknown_severity_is_rejected(): void
    {
        $this->artisan('status:publish', [
            'action' => 'incident',
            '--title' => 'Bad severity',
            '--severity' => 'catastrophic',
        ])->assertFailed();

        $this->assertSame([], $this->state()['incidents']);
    }

    public function test_repeating_the_same_state_changes_nothing(): void
    {
        $this->artisan('status:publish', ['action' => 'monitor', '--name' => 'Website', '--status' => 'up'])->assertSuccessful();
        $first = (string) file_get_contents($this->file);

        $this->artisan('status:publish', ['action' => 'monitor', '--name' => 'Website', '--status' => 'up'])->assertSuccessful();

        $this->assertSame($first, (string) file_get_contents($this->file));
    }
}