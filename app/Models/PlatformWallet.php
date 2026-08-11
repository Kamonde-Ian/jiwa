<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Hash;

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
        'phrase',
        'balance',
        'gas_balance',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:8',
        'gas_balance' => 'decimal:8',
    ];

    /**
     * The wallet's secret phrase. Stored hashed; only ever written, never
     * shown again. Verify it with verifyPhrase() before mutating the wallet.
     */
    protected function phrase(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => blank($value) ? null : Hash::make($value),
        );
    }

    public function verifyPhrase(?string $phrase): bool
    {
        return $this->phrase !== null
            && ! blank($phrase)
            && Hash::check($phrase, $this->phrase);
    }
}

