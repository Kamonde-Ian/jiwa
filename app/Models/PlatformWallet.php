<?php

namespace App\Models;

class PlatformWallet extends BaseModel
{
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const TYPES = [
        self::TYPE_DEPOSIT,
        self::TYPE_WITHDRAWAL,
    ];

    protected $fillable = [
        'name',
        'type',
        'network',
        'address',
        'balance',
        'gas_balance',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:8',
        'gas_balance' => 'decimal:8',
    ];
}
