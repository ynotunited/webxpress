<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Records CMS edits as audit rows.
 *
 * An observer rather than calls sprinkled through each Filament page, because a
 * hand-placed call is a call someone eventually forgets: an edit made through
 * tinker, a seeder, a console command or a future admin screen would then leave
 * no trace. Every write through Eloquent passes here instead.
 *
 * Recording is best-effort by design. If the audit insert fails — the table is
 * missing on a half-migrated database, the disk is full — the write that
 * triggered it must still succeed. Losing a content edit is worse than losing
 * the record of it, so the failure is reported to the log and swallowed. The
 * tests assert this explicitly, because it is the kind of decision that looks
 * like a bug if nobody writes down why.
 */
class AuditObserver
{
    /** Fields never written to the trail, whatever their value. */
    private const REDACTED = [
        'password',
        'password_hash',
        'remember_token',
        'api_key',
        'secret',
        'token',
    ];

    public function created(Model $model): void
    {
        $this->record('created', $model, null);
    }

    public function updated(Model $model): void
    {
        // getChanges() is post-save and correctly limited to dirty fields, so
        // this does not rewrite the entire row on every save.
        $this->record('updated', $model, $this->diff($model));
    }

    public function deleted(Model $model): void
    {
        $this->record('deleted', $model, null);
    }

    /**
     * The before/after pairs for this save.
     *
     * getOriginal() is read after the save because the observer fires post-save,
     * at which point getOriginal() still holds the pre-save values.
     */
    private function diff(Model $model): array
    {
        $changes = [];

        foreach ($model->getChanges() as $field => $value) {
            if (in_array($field, self::REDACTED, true) || str_contains($field, 'password')) {
                $changes[$field] = ['old' => null, 'new' => '[redacted]'];

                continue;
            }

            $before = $model->getOriginal($field);

            // Long bodies (post content, page sections) are truncated. The
            // useful signal is "this field changed", and a full copy of every
            // revision would turn the table into a second copy of the site.
            $changes[$field] = [
                'old' => $this->truncate($before),
                'new' => $this->truncate($value),
            ];
        }

        return $changes;
    }

    private function truncate(mixed $value): mixed
    {
        if (is_string($value) && mb_strlen($value) > 200) {
            return mb_substr($value, 0, 200).'… [truncated]';
        }

        return $value;
    }

    private function record(string $event, Model $model, ?array $changes): void
    {
        // Pivots fire the same model events for every attach/detach, which
        // would fill the trail with dozens of identical rows for one edit.
        if ($model instanceof Pivot) {
            return;
        }

        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'user_email' => Auth::user()?->email,
                'event' => $event,
                'subject_type' => $model->getMorphClass(),
                'subject_id' => $model->getKey(),
                'summary' => $this->summary($event, $model),
                'changes' => $changes,
                'ip_address' => Request::ip(),
                'user_agent' => mb_substr((string) Request::userAgent(), 0, 255),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * A sentence readable in a list, without opening the row.
     *
     * The title is resolved from a short allow-list rather than
     * $model->getAttribute('name'), so a model without that column cannot
     * throw while the audit insert is being written.
     */
    private function summary(string $event, Model $model): string
    {
        $label = class_basename($model);

        foreach (['title', 'name', 'heading', 'label', 'subject', 'email'] as $attribute) {
            if (array_key_exists($attribute, $model->getAttributes())) {
                $value = $model->getAttribute($attribute);

                if (filled($value) && is_scalar($value)) {
                    $verb = match ($event) {
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        default => ucfirst($event),
                    };

                    return sprintf('%s %s: %s', $verb, $label, mb_substr((string) $value, 0, 80));
                }
            }
        }

        return sprintf('%s %s #%s', ucfirst($event), $label, (string) $model->getKey());
    }
}