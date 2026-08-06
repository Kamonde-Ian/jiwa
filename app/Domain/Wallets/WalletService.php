<?php

namespace App\Domain\Wallets;

use App\Domain\Wallets\Exceptions\InsufficientBalanceException;
use App\Domain\Wallets\Exceptions\PrincipalWalletLockedException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Single entry point for every balance change in the platform.
 *
 * Nothing else in the codebase may mutate wallets.balance directly — all
 * credits, debits and transfers must flow through this service so the ledger
 * (wallet_transactions) stays complete and reconstructable.
 */
class WalletService
{
    public function __construct(protected string $defaultCurrency = 'USD')
    {
    }

    /**
     * Get (or lazily create) a wallet for a user.
     */
    public function getOrCreate(User $user, string $type, string $currency = null): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id, 'type' => $type, 'currency' => $currency ?? $this->defaultCurrency],
            ['balance' => 0],
        );
    }

    /**
     * Credit a wallet. Always allowed.
     */
    public function credit(
        User|Wallet $wallet,
        float $amount,
        ?string $description = null,
        object|string|null $reference = null,
        string $currency = null,
    ): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amount, $description, $reference, $currency) {
            $wallet = $this->resolve($wallet);

            if ($amount <= 0) {
                throw new \InvalidArgumentException('Credit amount must be positive.');
            }

            /** @var Wallet $locked */
            $locked = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->first();

            $balanceAfter = (float) $locked->balance + $amount;
            $locked->update(['balance' => $balanceAfter]);

            $transaction = WalletTransaction::create([
                'wallet_id' => $locked->id,
                'type' => WalletTransaction::TYPE_CREDIT,
                'amount' => $amount,
                'currency' => $currency ?? $locked->currency,
                'balance_after' => $balanceAfter,
                'reference_type' => $this->referenceType($reference),
                'reference_id' => $this->referenceId($reference),
                'description' => $description,
            ]);

            $this->log($locked, $transaction, 'credited');

            return $transaction;
        });
    }

    /**
     * Debit a wallet.
     *
     * The principal wallet may not be debited while the owner has any active
     * investment (locked principal), unless the caller explicitly opts in with
     * $allowLocked (internal allocations such as creating an investment).
     */
    public function debit(
        User|Wallet $wallet,
        float $amount,
        ?string $description = null,
        object|string|null $reference = null,
        string $currency = null,
        bool $allowLocked = false,
    ): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amount, $description, $reference, $currency, $allowLocked) {
            $wallet = $this->resolve($wallet);

            if ($amount <= 0) {
                throw new \InvalidArgumentException('Debit amount must be positive.');
            }

            /** @var Wallet $locked */
            $locked = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->first();

            if ($locked->type === Wallet::TYPE_PRINCIPAL && ! $allowLocked) {
                $hasActiveInvestment = $locked->user->investments()
                    ->where('status', 'active')
                    ->exists();

                if ($hasActiveInvestment) {
                    throw new PrincipalWalletLockedException(
                        'Principal wallet funds are locked while an investment is active.'
                    );
                }
            }

            if ((float) $locked->balance < $amount) {
                throw new InsufficientBalanceException(
                    "Insufficient balance in {$locked->type} wallet."
                );
            }

            $balanceAfter = (float) $locked->balance - $amount;
            $locked->update(['balance' => $balanceAfter]);

            $transaction = WalletTransaction::create([
                'wallet_id' => $locked->id,
                'type' => WalletTransaction::TYPE_DEBIT,
                'amount' => $amount,
                'currency' => $currency ?? $locked->currency,
                'balance_after' => $balanceAfter,
                'reference_type' => $this->referenceType($reference),
                'reference_id' => $this->referenceId($reference),
                'description' => $description,
            ]);

            $this->log($locked, $transaction, 'debited');

            return $transaction;
        });
    }

    /**
     * Move funds between two wallets atomically.
     */
    public function transfer(
        User|Wallet $from,
        User|Wallet $to,
        float $amount,
        ?string $description = null,
        object|string|null $reference = null,
        bool $allowLocked = false,
    ): array {
        return DB::transaction(function () use ($from, $to, $amount, $description, $reference, $allowLocked) {
            $debit = $this->debit($from, $amount, $description, $reference, allowLocked: $allowLocked);
            $credit = $this->credit($to, $amount, $description, $reference);

            return [$debit, $credit];
        });
    }

    public function balanceOf(User $user, string $type, string $currency = null): float
    {
        return (float) $this->getOrCreate($user, $type, $currency)->balance;
    }

    protected function resolve(User|Wallet $wallet): Wallet
    {
        return $wallet instanceof Wallet ? $wallet : $this->getOrCreate($wallet, Wallet::TYPE_PRINCIPAL);
    }

    protected function referenceType(object|string|null $reference): ?string
    {
        return is_object($reference) ? $reference::class : (is_string($reference) ? $reference : null);
    }

    protected function referenceId(object|string|null $reference): ?int
    {
        return is_object($reference) ? (int) $reference->getKey() : null;
    }

    protected function log(Wallet $wallet, WalletTransaction $transaction, string $verb): void
    {
        activity('wallet')
            ->performedOn($wallet)
            ->causedBy(auth()->user())
            ->withProperties([
                'action' => "wallet_{$verb}",
                'wallet_type' => $wallet->type,
                'amount' => $transaction->amount,
                'balance_after' => $transaction->balance_after,
                'transaction_id' => $transaction->id,
            ])
            ->log("{$transaction->amount} {$transaction->currency} {$verb} on {$wallet->type} wallet");
    }
}
