<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentPlan extends BaseModel
{
    protected $fillable = [
        'name',
        'duration_days',
        'daily_rate',
        'min_amount',
        'max_amount',
        'description',
        'is_active',
        'is_custom',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:6',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
    ];

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class, 'plan_id');
    }
}
