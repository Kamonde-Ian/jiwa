<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycVerification extends BaseModel
{
    public const STATUS_UNVERIFIED = 'unverified';
    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'document_type',
        'document_path',
        'document_back_path',
        'selfie_path',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'reviewed_at' => 'datetime',
    ];

    protected $hidden = [
        'document_path',
        'document_back_path',
        'selfie_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
