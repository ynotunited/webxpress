<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * One delivery attempt of one webhook event.
 *
 * Append-only in the same sense as AuditLog: the table is the evidence, and
 * evidence that can be edited is not evidence. `booted()` refuses updates and
 * deletes outright rather than relying on convention, so a later refactor
 * cannot quietly start rewriting history.
 *
 * The one permitted mutation is stamping the outcome of processing, which
 * `markQueued()` and `markProcessed()` do. That is not a contradiction: the
 * inbound bytes in `payload` never change, only this system's own record of
 * what it did with them.
 *
 * @property int $id
 * @property string $provider
 * @property string $event
 * @property string|null $reference
 * @property string $dedupe_key
 * @property string $payload
 * @property string|null $source_ip
 * @property bool $signature_valid
 * @property string $status
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon|null $payload_received_at
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property int $attempts
 */
class WebhookEvent extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_IGNORED = 'ignored';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    /**
     * Kept short enough to sit comfortably inside the unique index.
     */
    public const DEDUPE_PREFIX_LENGTH = 16;

    protected $fillable = [
        'provider',
        'event',
        'reference',
        'dedupe_key',
        'payload',
        'source_ip',
        'signature_valid',
        'status',
        'failure_reason',
        'payload_received_at',
        'processed_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'payload_received_at' => 'datetime',
            'processed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Build the identity used to detect a repeat delivery.
     *
     * Paystack sends no event id, so this combines the three fields that are
     * stable when the *same* event is redelivered: the provider, the event type
     * and the transaction reference.
     *
     * Three deliberate properties. A resend of one charge hashes to the same key
     * and is suppressed; a genuinely different charge against the same payment
     * produces a different key and is not suppressed, because hiding it would
     * discard real money. And an event with no reference still gets a key,
     * falling back to a hash of the body — otherwise every reference-less event
     * would collide on one empty string and the second would be dropped.
     *
     * `$discriminator` keeps refused attempts in their own namespace. A captured
     * genuine body replayed without a valid signature has byte-identical content
     * to the event already on file, so without this it would collide with it —
     * and the collision would be raised from a rejection path, turning an
     * unsigned request into a server error instead of a clean refusal.
     */
    public static function dedupeKey(
        string $provider,
        string $event,
        ?string $reference,
        ?string $rawPayload,
        string $discriminator = 'accepted',
    ): string {
        $identity = $reference !== null && $reference !== ''
            ? $provider.'|'.$discriminator.'|'.$event.'|'.$reference
            : $provider.'|'.$discriminator.'|'.$event.'|'.hash('sha256', (string) $rawPayload);

        return $provider.':'.substr(hash('sha256', $identity), 0, self::DEDUPE_PREFIX_LENGTH);
    }

    public function markQueued(): void
    {
        $this->forceFill([
            'status' => self::STATUS_QUEUED,
            'attempts' => $this->attempts + 1,
        ])->save();
    }

    public function markProcessed(): void
    {
        $this->forceFill([
            'status' => self::STATUS_PROCESSED,
            'failure_reason' => null,
            'processed_at' => now(),
        ])->save();
    }

    public function markFailed(string $reason): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'failure_reason' => mb_substr($reason, 0, 255),
            'processed_at' => now(),
        ])->save();
    }

    /**
     * A payment arrived that the site refused to act on — wrong amount, unknown
     * reference, deal already cancelled. Money is involved and a person has to
     * look, so this is surfaced rather than logged and forgotten.
     */
    public function needsAttention(): bool
    {
        return $this->status === self::STATUS_FAILED && $this->failure_reason !== null;
    }

    /**
     * @return array<int, class-string>
     */
    public function scopeUnhandled($query)
    {
        return $query->whereIn('status', [self::STATUS_RECEIVED, self::STATUS_QUEUED]);
    }

    /**
     * The columns this system is allowed to change after a row exists.
     *
     * Everything else — the payload, the signature verdict, the source address,
     * the timestamps Paystack sent — is what actually happened and is fixed.
     * Only our own record of having acted on it is mutable.
     *
     * @var array<int, string>
     */
    protected const MUTABLE_AFTER_INSERT = [
        'status',
        'failure_reason',
        'processed_at',
        'attempts',
    ];

    protected static function booted(): void
    {
        // Enforced per column rather than by blanket-refusing updates, because
        // stamping the processing outcome is the one legitimate write. A blanket
        // hook would have to be bypassed by the marking methods above, and code
        // that can bypass it can also rewrite the payload by accident.
        static::updating(function (self $event): void {
            $forbidden = array_diff(
                array_keys($event->getDirty()),
                self::MUTABLE_AFTER_INSERT
            );

            if ($forbidden !== []) {
                throw new LogicException(
                    'Webhook events are append-only. Refused to change: '.implode(', ', $forbidden).'.'
                );
            }
        });

        static::deleting(function (): never {
            throw new LogicException('Webhook events cannot be deleted.');
        });
    }
}