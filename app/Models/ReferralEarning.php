<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralEarning extends BaseModel
{
    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'investment_id',
        'amount',
        'rate',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:4',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }
}
