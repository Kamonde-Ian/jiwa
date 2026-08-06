<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Investment extends BaseModel
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_MATURED = 'matured';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'plan_id',
        'principal_amount',
        'daily_rate_snapshot',
        'status',
        'starts_at',
        'matures_at',
        'last_interest_credited_at',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:8',
        'daily_rate_snapshot' => 'decimal:6',
        'starts_at' => 'datetime',
        'matures_at' => 'datetime',
        'last_interest_credited_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class, 'plan_id');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(WalletTransaction::class, 'reference');
    }
}
