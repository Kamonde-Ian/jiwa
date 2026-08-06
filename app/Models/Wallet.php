<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends BaseModel
{
    public const TYPE_PRINCIPAL = 'principal';
    public const TYPE_EARNINGS = 'earnings';
    public const TYPE_REFERRAL = 'referral';

    public const TYPES = [
        self::TYPE_PRINCIPAL,
        self::TYPE_EARNINGS,
        self::TYPE_REFERRAL,
    ];

    protected $fillable = [
        'user_id',
        'type',
        'currency',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
