<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InquiryMessage extends Model
{
    protected $fillable = [
        'project_inquiry_id',
        'direction',
        'type',
        'subject',
        'body',
        'from_email',
        'from_name',
        'message_id',
        'in_reply_to',
        'headers',
        'received_at',
    ];

    public const DIR_INBOUND = 'inbound';

    public const DIR_OUTBOUND = 'outbound';

    public function inquiry()
    {
        return $this->belongsTo(ProjectInquiry::class, 'project_inquiry_id');
    }
}
