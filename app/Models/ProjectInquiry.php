<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectInquiry extends Model
{
    protected $fillable = [
        'name',
        'email',
        'project_type',
        'budget',
        'timeline',
        'message',
        'status',
        'thread_token',
    ];

    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_CLOSED = 'closed';

    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function messages()
    {
        return $this->hasMany(InquiryMessage::class, 'project_inquiry_id');
    }

    public function markContacted(): void
    {
        $this->update(['status' => self::STATUS_CONTACTED]);
    }
}
