<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealDocument extends Model
{
    public const TYPE_TEMPLATE = 'template';

    public const TYPE_UPLOAD = 'upload';

    public const KEY_PROPOSAL = 'proposal';

    public const KEY_TIMELINE = 'timeline';

    public const KEY_TERMS = 'terms';

    protected $fillable = [
        'deal_id',
        'type',
        'template_key',
        'title',
        'content',
        'file_path',
        'file_name',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
