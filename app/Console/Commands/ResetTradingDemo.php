<?php

namespace App\Console\Commands;

use App\Models\PoolAllocation;
use App\Models\TradingPool;
use App\Models\TradingSession;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTradingDemo extends Command
{
    protected $signature = 'jiwa:reset-trading-demo';

    protected $description = 'Reset all demo trading data: sessions, allocations, pool state, and restore the affected users\' wallet ledgers so no trading ever happened';

    public function handle(): int
    {
        $affectedWalletIds = collect();

        DB::transaction(function () use (&$affectedWalletIds) {
            // Wallets whose balance was touched by trading either hold an
            // allocation (the allocation debits came from principal) or carry
            // "Bot trading result" / "Bot fund withdrawal" credits.
            $affectedWalletIds = Wallet::query()
                ->whereHas('transactions', function ($q) {
                    $q->where(fn ($q2) => $q2
                        ->whereIn('reference_type', [PoolAllocation::class])
                        ->orWhere('description', 'like', 'Allocated to JIWA Bot Fund%')
                        ->orWhere('description', 'like', 'Bot trading result%')
                        ->orWhere('description', 'like', 'Bot fund withdrawal%'));
                })
                ->pluck('id');

            if ($affectedWalletIds->isNotEmpty()) {
                // Remove every transaction the trading engine produced so the
                // ledger reads as if trading never happened.
                WalletTransaction::query()
                    ->whereIn('wallet_id', $affectedWalletIds)
                    ->where(fn ($q) => $q
                        ->whereIn('reference_type', [PoolAllocation::class])
                        ->orWhere('description', 'like', 'Allocated to JIWA Bot Fund%')
                        ->orWhere('description', 'like', 'Bot trading result%')
                        ->orWhere('description', 'like', 'Bot fund withdrawal%'))
                    ->delete();

                // Recompose the remaining ledger for each wallet (keeps the
                // balance_after chain coherent) and reset the live balance.
                foreach (Wallet::query()->whereIn('id', $affectedWalletIds)->get() as $wallet) {
                    $running = 0.0;

                    foreach ($wallet->transactions()->orderBy('id')->get() as $tx) {
                        $running = $tx->type === WalletTransaction::TYPE_CREDIT
                            ? $running + (float) $tx->amount
                            : $running - (float) $tx->amount;

                        $tx->forceFill(['balance_after' => $running])->save();
                    }

                    $wallet->update(['balance' => $running]);
                }
            }

            // Wipe the trading records and return the pool to its pristine
            // state (base NAV, zero units, no last-return).
            $sessionCount = TradingSession::query()->count();
            TradingSession::query()->delete();

            $allocationCount = PoolAllocation::query()->count();
            PoolAllocation::query()->delete();

            foreach (TradingPool::query()->get() as $pool) {
                $pool->update([
                    'nav' => (float) $pool->base_nav,
                    'total_units' => 0,
                    'daily_return_pct' => null,
                    'nav_updated_at' => null,
                ]);
            }

            // Drop the trading activity trail too.
            $activityCount = DB::table('activity_log')
                ->where('log_name', 'trading')
                ->delete();

            $this->info("Sessions deleted: {$sessionCount}");
            $this->info("Allocations deleted: {$allocationCount}");
            $this->info("Trading activity logs deleted: {$activityCount}");
            $this->info("Wallets restored: {$affectedWalletIds->count()}");
        });

        return self::SUCCESS;
    }
}