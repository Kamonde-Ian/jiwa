<?php

namespace App\Domain\Investments;

use App\Domain\Wallets\WalletService;
use App\Events\InvestmentCreated;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class InvestmentService
{
    public function __construct(protected WalletService $walletService)
    {
    }

    /**
     * Create an investment: move funds from the user's Principal Wallet into
     * the locked investment, snapshotting the plan's current daily rate.
     */
    public function create(User $user, InvestmentPlan $plan, float $amount): Investment
    {
        return DB::transaction(function () use ($user, $plan, $amount) {
            $min = max((float) \App\Support\PlatformSettings::config('jiwa.min_investment'), (float) $plan->min_amount);

            if ($amount < $min) {
                throw new \InvalidArgumentException("Minimum investment for this plan is \${$min}.");
            }

            if ($plan->max_amount !== null && $amount > (float) $plan->max_amount) {
                throw new \InvalidArgumentException("Maximum investment for this plan is \${$plan->max_amount}.");
            }

            if (! $plan->is_active) {
                throw new \InvalidArgumentException('This investment plan is not active.');
            }

            $principal = $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL);

            // Internal allocation: principal moves into the locked investment.
            $this->walletService->debit(
                $principal,
                $amount,
                'Funds allocated to investment',
                null,
                allowLocked: true,
            );

            $startsAt = now();
            $investment = Investment::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'principal_amount' => $amount,
                'daily_rate_snapshot' => $plan->daily_rate,
                'status' => Investment::STATUS_ACTIVE,
                'starts_at' => $startsAt,
                'matures_at' => $startsAt->copy()->addDays($plan->duration_days),
                'last_interest_credited_at' => $startsAt,
            ]);

            activity('investment')
                ->performedOn($investment)
                ->causedBy($user)
                ->withProperties([
                    'action' => 'investment_created',
                    'principal' => $amount,
                    'plan' => $plan->name,
                ])
                ->log('Investment created');

            event(new InvestmentCreated($investment));

            return $investment;
        });
    }

    /**
     * Credit daily interest to every eligible active investment.
     *
     * Idempotent: an investment only receives interest once every
     * interest_credit_hours window. Re-running this method within the same
     * window never double-credits.
     *
     * @return int number of interest credits applied
     */
    public function creditDailyInterest(CarbonImmutable $now = null): int
    {
        $now = $now ?? CarbonImmutable::now();
        $creditHours = (int) \App\Support\PlatformSettings::config('jiwa.interest_credit_hours');
        $credited = 0;

        Investment::query()
            ->where('status', Investment::STATUS_ACTIVE)
            ->where('matures_at', '>', $now)
            ->where(function ($q) use ($now, $creditHours) {
                $q->whereNull('last_interest_credited_at')
                    ->orWhere('last_interest_credited_at', '<=', $now->subHours($creditHours));
            })
            ->orderBy('id')
            ->chunkById(500, function ($investments) use ($now, $creditHours, &$credited) {
                foreach ($investments as $investment) {
                    DB::transaction(function () use ($investment, $now, $creditHours, &$credited) {
                        // Re-check inside the transaction to stay safe under concurrency.
                        if ($investment->fresh()->status !== Investment::STATUS_ACTIVE) {
                            return;
                        }

                        if ($investment->last_interest_credited_at
                            && $investment->last_interest_credited_at->greaterThan($now->subHours($creditHours))) {
                            return;
                        }

                        $interest = round((float) $investment->principal_amount * (float) $investment->daily_rate_snapshot, 8);

                        $earnings = $this->walletService->getOrCreate(
                            $investment->user,
                            Wallet::TYPE_EARNINGS,
                        );

                        $this->walletService->credit(
                            $earnings,
                            $interest,
                            "Daily interest — {$investment->plan->name}",
                            $investment,
                        );

                        $investment->update(['last_interest_credited_at' => $now]);

                        activity('interest')
                            ->performedOn($investment)
                            ->causedBy(null)
                            ->withProperties([
                                'action' => 'interest_credited',
                                'amount' => $interest,
                                'principal' => $investment->principal_amount,
                                'rate' => $investment->daily_rate_snapshot,
                            ])
                            ->log('Daily interest credited');

                        $credited++;
                    });
                }
            });

        return $credited;
    }

    /**
     * Mark active investments as matured once matures_at passes and release
     * the principal to a withdrawable state.
     *
     * @return int number of investments matured
     */
    public function processMaturities(CarbonImmutable $now = null): int
    {
        $now = $now ?? CarbonImmutable::now();
        $matured = 0;

        Investment::query()
            ->where('status', Investment::STATUS_ACTIVE)
            ->where('matures_at', '<=', $now)
            ->orderBy('id')
            ->chunkById(500, function ($investments) use (&$matured) {
                foreach ($investments as $investment) {
                    DB::transaction(function () use ($investment, &$matured) {
                        if ($investment->fresh()->status !== Investment::STATUS_ACTIVE) {
                            return;
                        }

                        $destination = \App\Support\PlatformSettings::config('jiwa.matured_principal_destination');
                        $walletType = $destination === 'earnings' ? Wallet::TYPE_EARNINGS : Wallet::TYPE_PRINCIPAL;

                        $wallet = $this->walletService->getOrCreate($investment->user, $walletType);

                        $this->walletService->credit(
                            $wallet,
                            (float) $investment->principal_amount,
                            "Matured principal returned — {$investment->plan->name}",
                            $investment,
                        );

                        $investment->update(['status' => Investment::STATUS_MATURED]);

                        activity('investment')
                            ->performedOn($investment)
                            ->causedBy(null)
                            ->withProperties([
                                'action' => 'investment_matured',
                                'principal' => $investment->principal_amount,
                            ])
                            ->log('Investment matured, principal released');

                        $matured++;
                    });
                }
            });

        return $matured;
    }
}
