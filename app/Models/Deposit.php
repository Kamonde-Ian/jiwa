<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends BaseModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'network',
        'currency',
        'tx_hash',
        'amount_currency',
        'amount_usd',
        'status',
        'admin_note',
        'confirmed_by',
        'confirmed_at',
        'rejected_at',
    ];

    protected $casts = [
        'amount_currency' => 'decimal:8',
        'amount_usd' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function confirmor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
