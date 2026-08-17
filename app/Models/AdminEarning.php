<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminEarning extends BaseModel
{
    protected $fillable = [
        'admin_id',
        'source_type',
        'source_id',
        'amount',
        'rate',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:4',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}