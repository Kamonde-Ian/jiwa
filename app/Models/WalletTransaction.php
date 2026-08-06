<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends BaseModel
{
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'currency',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'balance_after' => 'decimal:8',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
