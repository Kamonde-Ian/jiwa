<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradingSession extends BaseModel
{
    protected $fillable = [
        'pool_id',
        'session_date',
        'open_nav',
        'high_nav',
        'low_nav',
        'close_nav',
        'return_pct',
        'pnl',
        'is_profit',
        'trade_count',
        'strategy',
    ];

    protected $casts = [
        'session_date' => 'date',
        'open_nav' => 'decimal:8',
        'high_nav' => 'decimal:8',
        'low_nav' => 'decimal:8',
        'close_nav' => 'decimal:8',
        'return_pct' => 'decimal:4',
        'pnl' => 'decimal:2',
        'is_profit' => 'boolean',
    ];

    public function pool(): BelongsTo
    {
        return $this->belongsTo(TradingPool::class, 'pool_id');
    }
}