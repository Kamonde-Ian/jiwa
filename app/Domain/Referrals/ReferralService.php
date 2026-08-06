<?php

namespace App\Domain\Referrals;

use App\Domain\Wallets\WalletService;
use App\Models\Investment;
use App\Models\ReferralEarning;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    public function __construct(protected WalletService $walletService)
    {
    }

    /**
     * A referrer is qualified once they hold an active investment of at least
     * the configured minimum amount.
     */
    public function isQualified(User $referrer): bool
    {
        $minimum = (float) \App\Support\PlatformSettings::config('jiwa.referral_qualification_minimum');

        return $referrer->investments()
            ->where('status', Investment::STATUS_ACTIVE)
            ->where('principal_amount', '>=', $minimum)
            ->exists();
    }

    public function referralLink(User $referrer): string
    {
        return route('register', ['ref' => $referrer->referral_code]);
    }

    /**
     * Credit a one-time commission to the referrer of the user who made this
     * investment. Idempotent: one ReferralEarning per investment.
     *
     * @return ReferralEarning|null null when no commission is earned
     */
    public function creditCommission(Investment $investment): ?ReferralEarning
    {
        return DB::transaction(function () use ($investment) {
            if (ReferralEarning::where('investment_id', $investment->id)->exists()) {
                return null;
            }

            $investor = $investment->user;

            if (! $investor->referred_by) {
                return null;
            }

            $referrer = $investor->referredBy;

            if (! $referrer || ! $this->isQualified($referrer)) {
                return null;
            }

            $rate = (float) \App\Support\PlatformSettings::config('jiwa.referral_commission_rate');
            $amount = round((float) $investment->principal_amount * $rate, 2);

            if ($amount <= 0) {
                return null;
            }

            $wallet = $this->walletService->getOrCreate($referrer, Wallet::TYPE_REFERRAL);

            $this->walletService->credit(
                $wallet,
                $amount,
                "Referral commission — {$investor->name} invested",
                $investment,
            );

            $earning = ReferralEarning::create([
                'referrer_id' => $referrer->id,
                'referred_user_id' => $investor->id,
                'investment_id' => $investment->id,
                'amount' => $amount,
                'rate' => $rate,
            ]);

            activity('referral')
                ->performedOn($referrer)
                ->causedBy($investor)
                ->withProperties([
                    'action' => 'referral_commission_credited',
                    'amount' => $amount,
                    'rate' => $rate,
                    'referred_user_id' => $investor->id,
                ])
                ->log("Referral commission of \${$amount} credited");

            return $earning;
        });
    }
}
