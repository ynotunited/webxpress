<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Receipt extends Model
{
    protected $fillable = [
        'payment_id',
        'receipt_no',
        'amount',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function deal(): HasOneThrough
    {
        return $this->hasOneThrough(
            Deal::class,
            Payment::class,
            'id',
            'id',
            'payment_id',
            'deal_id',
        );
    }
}
