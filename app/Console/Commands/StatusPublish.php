<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The single way the status site's data gets updated.
 *
 * The status page lives in status/ and is deployed to Cloudflare Pages on its
 * own domain — the whole point being that it keeps serving when this
 * application stops serving. All it ever reads is status/data/status.json, so
 * this command owns writing that file: what it prints is what the public sees.
 *
 * Publishing an update is only step one of a deploy. The output ends with the
 * reminder to commit and push, because Cloudflare Pages redeploys from git —
 * nothing has gone live until status/data is committed.
 */
class StatusPublish extends Command
{
    protected $signature = 'status:publish {action : monitor | incident | maintenance}
        {--name= : monitor name (one action only)}
        {--status= : monitor state: up|degraded|down|maintenance}
        {--title= : incident or maintenance title}
        {--severity= : minor|major|critical}
        {--state= : investigating|identified|monitoring|resolved}
        {--message= : message for the timeline or the maintenance notice}
        {--id= : existing incident/maintenance id to update or cancel}
        {--starts= : maintenance start, e.g. "2026-10-10 21:00"}
        {--ends= : maintenance end, e.g. "2026-10-11 03:00"}
        {--cancel : cancel the identified maintenance window}
        {--overall= : force overall status instead of deriving it}';

    protected $description = 'Publish an update to the status site (status/data/status.json)';

    private array $monitorStates = ['up', 'degraded', 'down', 'maintenance'];

    private array $severities = ['minor', 'major', 'critical'];

    private array $incidentStates = ['investigating', 'identified', 'monitoring', 'resolved'];

    private array $overallStates = ['operational', 'degraded', 'down', 'maintenance'];

    public function handle(): int
    {
        $action = $this->argument('action');

        if (! in_array($action, ['monitor', 'incident', 'maintenance'], true)) {
            $this->error("Unknown action [{$action}]. Use monitor, incident or maintenance.");

            return self::FAILURE;
        }

        $path = config('status.path');

        if (! is_dir($path)) {
            $this->error("Status data directory does not exist: {$path}");

            return self::FAILURE;
        }

        $file = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'status.json';
        $data = $this->load($file);
        $before = $data;

        try {
            [$data, $summary] = $this->apply($action, $data);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // Compare before touching updated_at, so a publish that changes nothing
        // is a genuine no-op instead of an updated_at bump every time.
        if ($data === $before) {
            $this->info('No change: the status was already exactly this.');
            $this->info($summary);

            return self::SUCCESS;
        }

        $data['updated_at'] = Carbon::now()->toISOString();

        file_put_contents(
            $file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL
        );

        $this->info($summary);
        $this->comment('status/data/status.json updated.');
        $this->comment('Commit and push — Cloudflare Pages only redeploys from git:');
        $this->comment('    git add status/data && git commit -m "status: '."{$summary}".'" && git push');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function apply(string $action, array $data): array
    {
        return match ($action) {
            'monitor' => $this->updateMonitor($data),
            'incident' => $this->publishIncident($data),
            'maintenance' => $this->publishMaintenance($data),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function updateMonitor(array $data): array
    {
        $name = $this->required('name');
        $status = $this->option('status') ?? 'up';

        if (! in_array($status, $this->monitorStates, true)) {
            throw new \InvalidArgumentException(
                "Monitor status must be one of: ".implode(', ', $this->monitorStates).". Got [{$status}]."
            );
        }

        $monitors = $data['monitors'];
        $key = array_search($name, array_column($monitors, 'name'), true);
        $monitor = ['name' => $name, 'status' => $status];

        if ($key === false) {
            $monitors[] = $monitor;
        } else {
            $previous = $monitors[$key];
            $monitors[$key] = $monitor;

            if ($previous['status'] === $monitor['status']) {
                return [$data, "Monitor [{$name}] is already reporting [{$status}]."];
            }

            // The monitor flipping down is supposed to be the moment the person
            // on call opens an incident — that is the side of the workflow the
            // status page cannot automate, and calling it out here keeps the
            // step in front of whoever pushed the button.
            $this->remindOnTransition($previous, $monitor);
        }

        $data['monitors'] = $monitors;
        $data['overall'] = $this->overall($data);

        return [$data, "Monitor [{$name}] now reporting [{$status}]."];
    }

    private function remindOnTransition(array $previous, array $monitor): void
    {
        if ($previous['status'] !== 'up' && $monitor['status'] === 'up') {
            $this->comment('Monitor came back up. Resolve or close any incident covering it.');

            return;
        }

        if ($previous['status'] === 'up' && $monitor['status'] !== 'up') {
            $this->comment(
                'Monitor ['.$monitor['name'].'] is no longer up — open an incident for it if one is not already active.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function publishIncident(array $data): array
    {
        $id = $this->option('id');

        if ($id !== null) {
            return $this->updateIncident($data, (string) $id);
        }

        $title = $this->required('title');
        $severity = $this->option('severity') ?? 'minor';
        $state = $this->option('state') ?? 'investigating';
        $message = $this->option('message') ?? 'We are investigating.';

        $this->assertIn($severity, $this->severities, 'severity');
        $this->assertIn($state, $this->incidentStates, 'state');

        $id = $this->incidentId($title);

        $data['incidents'][] = [
            'id' => $id,
            'title' => $title,
            'severity' => $severity,
            'status' => $state,
            'started_at' => Carbon::now()->toISOString(),
            'resolved_at' => null,
            'updates' => [[
                'time' => Carbon::now()->toISOString(),
                'status' => $state,
                'message' => $message,
            ]],
        ];

        $data['overall'] = $this->overall($data);

        return [$data, "Opened incident [{$id}] at [{$state}] severity [{$severity}]: {$title}"];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function updateIncident(array $data, string $id): array
    {
        $state = $this->required('state', "provide --state= when updating incident [{$id}]");
        $message = $this->required('message', "provide --message= when updating incident [{$id}]");

        $this->assertIn($state, $this->incidentStates, 'state');

        $key = array_search($id, array_column($data['incidents'], 'id'), true);

        if ($key === false) {
            throw new \InvalidArgumentException("No incident with id [{$id}].");
        }

        $incident = $data['incidents'][$key];
        $incident['updates'][] = [
            'time' => Carbon::now()->toISOString(),
            'status' => $state,
            'message' => $message,
        ];
        $incident['status'] = $state;

        if ($state === 'resolved') {
            $incident['resolved_at'] = Carbon::now()->toISOString();

            $this->comment('Incident resolved. The post-mortem that makes the record useful belongs in the status README.');
        } elseif ($incident['resolved_at'] !== null) {
            $incident['resolved_at'] = null;
        }

        $data['incidents'][$key] = $incident;
        $data['overall'] = $this->overall($data);

        return [$data, "Incident [{$id}] now [{$state}]."];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function publishMaintenance(array $data): array
    {
        $id = $this->option('id');
        $cancel = (bool) $this->option('cancel');

        if ($cancel) {
            return $this->cancelMaintenance($data, (string) $this->required('id', 'provide --id= of the window to cancel'));
        }

        $title = $this->required('title');
        $starts = $this->required('starts', 'provide --starts="Y-m-d H:i" for the maintenance window');
        $ends = $this->required('ends', 'provide --ends="Y-m-d H:i" for the maintenance window');

        $start = $this->parseDate($starts);
        $end = $this->parseDate($ends);

        if ($end->lt($start)) {
            throw new \InvalidArgumentException('Maintenance cannot end before it starts.');
        }

        if ($id !== null) {
            $key = array_search($id, array_column($data['maintenance'], 'id'), true);

            if ($key === false) {
                throw new \InvalidArgumentException("No maintenance with id [{$id}].");
            }

            $window = $data['maintenance'][$key];
            $window['title'] = $title;
            $window['starts_at'] = $start->toISOString();
            $window['ends_at'] = $end->toISOString();
            $window['message'] = $this->option('message') ?? $window['message'] ?? '';
            $window['cancelled'] = false;
            $data['maintenance'][$key] = $window;

            $data['overall'] = $this->overall($data);

            return [$data, "Rescheduled maintenance [{$id}]: {$title} ({$start} to {$end})."];
        }

        $windowId = 'mnt-'.$start->format('Y-m-d').'-'.Str::slug($title);
        $flip = 2;

        while (array_search($windowId, array_column($data['maintenance'], 'id'), true) !== false) {
            $windowId = 'mnt-'.$start->format('Y-m-d').'-'.Str::slug($title).'-'.$flip++;
        }

        $data['maintenance'][] = [
            'id' => $windowId,
            'title' => $title,
            'starts_at' => $start->toISOString(),
            'ends_at' => $end->toISOString(),
            'message' => $this->option('message') ?? '',
            'cancelled' => false,
        ];

        $data['overall'] = $this->overall($data);

        return [$data, "Scheduled maintenance [{$windowId}] {$start} to {$end}: {$title}"];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function cancelMaintenance(array $data, string $id): array
    {
        $key = array_search($id, array_column($data['maintenance'], 'id'), true);

        if ($key === false) {
            throw new \InvalidArgumentException("No maintenance with id [{$id}].");
        }

        $data['maintenance'][$key]['cancelled'] = true;
        $data['overall'] = $this->overall($data);

        return [$data, "Cancelled maintenance [{$id}]."];
    }

    /**
     * Derive the overall status: the worst thing happening wins. A critical
     * incident beats a scheduled window; an active window beats nothing.
     *
     * @param  array<string, mixed>  $data
     */
    private function overall(array $data): string
    {
        if ($overall = $this->option('overall')) {
            $this->assertIn($overall, $this->overallStates, 'overall');

            return $overall;
        }

        $active = collect($data['incidents'])
            ->filter(fn (array $i): bool => $i['status'] !== 'resolved');

        if ($active->contains(fn (array $i): bool => $i['severity'] === 'critical')) {
            return 'down';
        }

        if ($active->isNotEmpty()) {
            return 'degraded';
        }

        $worst = collect($data['monitors'])
            ->sortByDesc(fn (array $m): int => match ($m['status']) {
                'down' => 3,
                'degraded' => 2,
                'maintenance' => 1,
                default => 0,
            })
            ->first();

        if ($worst !== null && in_array($worst['status'], ['down', 'degraded'], true)) {
            return $worst['status'];
        }

        $activeWindow = collect($data['maintenance'])
            ->first(fn (array $m): bool => ! $m['cancelled'] && now()->between($m['starts_at'], $m['ends_at']));

        return $activeWindow !== null ? 'maintenance' : 'operational';
    }

    private function parseDate(string $value): Carbon
    {
        $date = Carbon::parse($value);

        if ($date->year < 2000) {
            throw new \InvalidArgumentException("Could not read [{$value}] as a date.");
        }

        return $date;
    }

    private function incidentId(string $title): string
    {
        $base = 'inc-'.Carbon::now()->format('Y-m-d').'-'.Str::slug($title);

        return $base.'-'.strtolower(Str::random(4));
    }

    private function required(string $option, ?string $message = null): string
    {
        $value = $this->option($option);

        if (! is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException($message ?? "Missing required --{$option}.");
        }

        return trim($value);
    }

    private function assertIn(string $value, array $allowed, string $label): void
    {
        if (! in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException(
                "{$label} must be one of: ".implode(' | ', $allowed).". Got [{$value}]."
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function load(string $file): array
    {
        if (! is_file($file)) {
            return $this->fresh();
        }

        $json = json_decode((string) file_get_contents($file), true);

        return is_array($json) && isset($json['monitors']) ? $json : $this->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function fresh(): array
    {
        return [
            'name' => config('app.name', 'Webxpress Technologies'),
            'homepage' => config('app.url'),
            'updated_at' => null,
            'overall' => 'operational',
            'monitors' => [],
            'incidents' => [],
            'maintenance' => [],
        ];
    }
}