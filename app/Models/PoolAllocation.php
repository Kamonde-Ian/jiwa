<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoolAllocation extends BaseModel
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'pool_id',
        'user_id',
        'units',
        'settled_amount',
        'status',
        'allocated_at',
        'closed_at',
    ];

    protected $casts = [
        'units' => 'decimal:8',
        'settled_amount' => 'decimal:2',
        'allocated_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function pool(): BelongsTo
    {
        return $this->belongsTo(TradingPool::class, 'pool_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Current market value of this position: units held at the pool's NAV.
     */
    public function currentValue(float $nav = null): float
    {
        $nav = $nav ?? (float) $this->pool->nav;

        return (float) $this->units * $nav;
    }
}