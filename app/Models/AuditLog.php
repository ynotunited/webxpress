<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One recorded action. Read-only by design.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $user_email
 * @property string $event
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string $summary
 * @property array|null $changes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class AuditLog extends Model
{
    /** No updated_at column exists, and none should. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_email',
        'event',
        'subject_type',
        'subject_id',
        'summary',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The label class name without the namespace, e.g. "Page".
     *
     * audit_logs stores the FQCN so it stays valid across a namespace refactor,
     * but the admin list reads better with the short name.
     */
    public function subjectLabel(): string
    {
        return class_basename((string) $this->subject_type);
    }

    /**
     * Refuse to alter or remove a recorded fact.
     *
     * Throwing rather than returning false on purpose: returning false cancels
     * the operation silently, so a "prune old audit rows" command would report
     * success while the table kept growing. Failing loudly means the mistake
     * surfaces at the point it is made.
     *
     * Note this guards the Eloquent path. A raw query builder call such as
     * AuditLog::query()->delete() skips model events, so pruning has to be a
     * deliberate, separate decision rather than an everyday Eloquent call.
     */
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log rows are append-only and cannot be updated.'));
        static::deleting(fn () => throw new LogicException('Audit log rows are append-only and cannot be deleted.'));
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}