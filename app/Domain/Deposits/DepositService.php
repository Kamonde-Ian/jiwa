<?php

namespace App\Domain\Deposits;

use App\Domain\Wallets\WalletService;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class DepositService
{
    public function __construct(protected WalletService $walletService)
    {
    }

    /**
     * Record a user's deposit request against a submitted transaction hash.
     * Confirmation is performed by an admin after verifying the chain.
     */
    public function request(User $user, string $network, string $txHash, float $amountUsd, float $amountCurrency = null): Deposit
    {
        $networks = (array) config('jiwa.networks');

        if (! isset($networks[$network])) {
            throw new \InvalidArgumentException('Unsupported network.');
        }

        if ($amountUsd <= 0) {
            throw new \InvalidArgumentException('Deposit amount must be positive.');
        }

        if (Deposit::where('tx_hash', $txHash)->exists()) {
            throw new \InvalidArgumentException('This transaction hash has already been submitted.');
        }

        $currency = $networks[$network]['currency'];

        return Deposit::create([
            'user_id' => $user->id,
            'network' => $network,
            'currency' => $currency,
            'tx_hash' => $txHash,
            'amount_usd' => $amountUsd,
            'amount_currency' => $amountCurrency,
            'status' => Deposit::STATUS_PENDING,
        ]);
    }

    /**
     * Confirm a pending deposit and credit the user's principal wallet.
     * Idempotent: already-confirmed deposits are never double-credited.
     */
    public function confirm(Deposit $deposit, User $admin, ?string $note = null): Deposit
    {
        return DB::transaction(function () use ($deposit, $admin, $note) {
            $deposit = $deposit->fresh();

            if ($deposit->status !== Deposit::STATUS_PENDING) {
                return $deposit;
            }

            $wallet = $this->walletService->getOrCreate($deposit->user, Wallet::TYPE_PRINCIPAL);

            $this->walletService->credit(
                $wallet,
                (float) $deposit->amount_usd,
                "Crypto deposit credited — {$deposit->network}",
                $deposit,
            );

            $deposit->update([
                'status' => Deposit::STATUS_CONFIRMED,
                'admin_note' => $note ?? $deposit->admin_note,
                'confirmed_by' => $admin->id,
                'confirmed_at' => now(),
            ]);

            activity('deposit')
                ->performedOn($deposit)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'deposit_confirmed',
                    'tx_hash' => $deposit->tx_hash,
                    'amount' => $deposit->amount_usd,
                    'network' => $deposit->network,
                ])
                ->log('Deposit confirmed and credited');

            return $deposit;
        });
    }

    public function reject(Deposit $deposit, User $admin, ?string $reason = null): Deposit
    {
        if ($deposit->status !== Deposit::STATUS_PENDING) {
            throw new \LogicException('Only pending deposits can be rejected.');
        }

        $deposit->update([
            'status' => Deposit::STATUS_REJECTED,
            'admin_note' => $reason,
            'confirmed_by' => $admin->id,
            'rejected_at' => now(),
        ]);

        activity('deposit')
            ->performedOn($deposit)
            ->causedBy($admin)
            ->withProperties([
                'action' => 'deposit_rejected',
                'tx_hash' => $deposit->tx_hash,
                'reason' => $reason,
            ])
            ->log('Deposit rejected');

        return $deposit;
    }
}
