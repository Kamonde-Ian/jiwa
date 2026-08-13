<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class TradingPool extends BaseModel
{
    protected $fillable = [
        'name',
        'symbol',
        'currency',
        'base_nav',
        'nav',
        'total_units',
        'nav_updated_at',
        'min_allocate',
        'max_allocate',
        'daily_return_pct',
        'description',
        'is_active',
        'is_running',
    ];

    protected $casts = [
        'base_nav' => 'decimal:8',
        'nav' => 'decimal:8',
        'total_units' => 'decimal:8',
        'nav_updated_at' => 'datetime',
        'min_allocate' => 'decimal:2',
        'max_allocate' => 'decimal:2',
        'daily_return_pct' => 'decimal:4',
        'is_active' => 'boolean',
        'is_running' => 'boolean',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(PoolAllocation::class, 'pool_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TradingSession::class, 'pool_id');
    }
}