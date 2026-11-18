<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deal extends Model
{
    use HasFactory;
    public const STATUS_DRAFT = 'draft';

    public const STATUS_DOCUMENTS_SENT = 'documents_sent';

    public const STATUS_UPFRONT_PENDING = 'upfront_pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DELIVERY_PENDING = 'delivery_pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'project_inquiry_id',
        'name',
        'email',
        'phone',
        'company',
        'project_title',
        'project_type',
        'scope',
        'amount',
        'upfront_percent',
        'delivery_percent',
        'start_date',
        'duration_label',
        'milestones',
        'terms',
        'notes',
        'status',
        'access_token',
        'created_via',
        'documents_sent_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'upfront_percent' => 70,
        'delivery_percent' => 30,
        'created_via' => 'manual',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'upfront_percent' => 'integer',
            'delivery_percent' => 'integer',
            'start_date' => 'date',
            'documents_sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_DOCUMENTS_SENT => 'Documents sent',
            self::STATUS_UPFRONT_PENDING => 'Awaiting upfront payment',
            self::STATUS_IN_PROGRESS => 'In progress',
            self::STATUS_DELIVERY_PENDING => 'Awaiting delivery payment',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(ProjectInquiry::class, 'project_inquiry_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DealDocument::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function upfrontPayment(): ?Payment
    {
        return $this->payments()->where('kind', Payment::KIND_UPFRONT)->first();
    }

    public function deliveryPayment(): ?Payment
    {
        return $this->payments()->where('kind', Payment::KIND_DELIVERY)->first();
    }

    public function upfrontPaid(): bool
    {
        $payment = $this->upfrontPayment();

        return $payment !== null && $payment->status === Payment::STATUS_PAID;
    }

    public function deliveryPaid(): bool
    {
        $payment = $this->deliveryPayment();

        return $payment !== null && $payment->status === Payment::STATUS_PAID;
    }

    public function portalUrl(): string
    {
        return route('deal.portal', $this->access_token);
    }
}
