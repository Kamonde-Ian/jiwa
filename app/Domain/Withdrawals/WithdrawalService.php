<?php

namespace App\Domain\Withdrawals;

use App\Domain\Wallets\Exceptions\InsufficientBalanceException;
use App\Domain\Wallets\Exceptions\PrincipalWalletLockedException;
use App\Domain\Wallets\WalletService;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Support\TwoFactorAuth;
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
    public function __construct(
        protected WalletService $walletService,
        protected TwoFactorAuth $twoFactorAuth,
    ) {
    }

    /**
     * Request a withdrawal: verifies KYC + 2FA, freezes the funds by debiting
     * the source wallet, and routes the request to review or auto-approval.
     */
    public function request(
        User $user,
        string $walletType,
        float $amount,
        string $network,
        string $destinationAddress,
        string $otp,
    ): Withdrawal {
        return DB::transaction(function () use ($user, $walletType, $amount, $network, $destinationAddress, $otp) {
            $this->assertEligible($user, $otp);

            if (! in_array($walletType, [Wallet::TYPE_PRINCIPAL, Wallet::TYPE_EARNINGS, Wallet::TYPE_REFERRAL], true)) {
                throw new \InvalidArgumentException('Unsupported wallet type.');
            }

            if (! isset(config('jiwa.networks')[$network])) {
                throw new \InvalidArgumentException('Unsupported network.');
            }

            $min = (float) \App\Support\PlatformSettings::config('jiwa.min_withdrawal');
            if ($amount < $min) {
                throw new \InvalidArgumentException("Minimum withdrawal is \${$min}.");
            }

            $fee = (float) \App\Support\PlatformSettings::config('jiwa.withdrawal_fee');
            $total = $amount + $fee;

            $wallet = $this->walletService->getOrCreate($user, $walletType);

            // Freeze the funds immediately so overlapping requests can never
            // overdraw the wallet.
            $this->walletService->debit(
                $wallet,
                $total,
                'Withdrawal request (funds frozen)',
                null,
            );

            $status = $this->initialStatus($total);

            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'wallet_type' => $walletType,
                'amount' => $amount,
                'fee' => $fee,
                'network' => $network,
                'destination_address' => $destinationAddress,
                'status' => $status,
            ]);

            activity('withdrawal')
                ->performedOn($withdrawal)
                ->causedBy($user)
                ->withProperties([
                    'action' => 'withdrawal_requested',
                    'wallet_type' => $walletType,
                    'amount' => $amount,
                    'fee' => $fee,
                    'status' => $status,
                ])
                ->log('Withdrawal requested');

            return $withdrawal;
        });
    }

    /**
     * Mark a withdrawal as approved (funds already frozen at request time).
     */
    public function approve(Withdrawal $withdrawal, User $admin, ?string $note = null): Withdrawal
    {
        if (! in_array($withdrawal->status, [Withdrawal::STATUS_PENDING_REVIEW, Withdrawal::STATUS_APPROVED], true)) {
            throw new \LogicException('Only reviewable withdrawals can be approved.');
        }

        $withdrawal->update([
            'status' => Withdrawal::STATUS_APPROVED,
            'admin_note' => $note ?? $withdrawal->admin_note,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        activity('withdrawal')
            ->performedOn($withdrawal)
            ->causedBy($admin)
            ->withProperties(['action' => 'withdrawal_approved'])
            ->log('Withdrawal approved');

        return $withdrawal;
    }

    /**
     * Mark a withdrawal as paid out (admin sent the crypto off-chain).
     */
    public function complete(Withdrawal $withdrawal, User $admin, ?string $note = null): Withdrawal
    {
        if ($withdrawal->status !== Withdrawal::STATUS_APPROVED) {
            throw new \LogicException('Only approved withdrawals can be completed.');
        }

        $withdrawal->update([
            'status' => Withdrawal::STATUS_COMPLETED,
            'admin_note' => $note ?? $withdrawal->admin_note,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        activity('withdrawal')
            ->performedOn($withdrawal)
            ->causedBy($admin)
            ->withProperties(['action' => 'withdrawal_completed'])
            ->log('Withdrawal completed');

        return $withdrawal;
    }

    /**
     * Reject a withdrawal and refund the frozen funds to the source wallet.
     */
    public function reject(Withdrawal $withdrawal, User $admin, ?string $reason = null): Withdrawal
    {
        if (! in_array($withdrawal->status, [Withdrawal::STATUS_PENDING_REVIEW, Withdrawal::STATUS_APPROVED], true)) {
            throw new \LogicException('Only reviewable withdrawals can be rejected.');
        }

        return DB::transaction(function () use ($withdrawal, $admin, $reason) {
            $wallet = $this->walletService->getOrCreate($withdrawal->user, $withdrawal->wallet_type);

            $this->walletService->credit(
                $wallet,
                (float) $withdrawal->amount + (float) $withdrawal->fee,
                'Withdrawal rejected — funds returned',
                $withdrawal,
            );

            $withdrawal->update([
                'status' => Withdrawal::STATUS_REJECTED,
                'admin_note' => $reason,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            activity('withdrawal')
                ->performedOn($withdrawal)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'withdrawal_rejected',
                    'reason' => $reason,
                ])
                ->log('Withdrawal rejected, funds returned');

            return $withdrawal;
        });
    }

    /**
     * A user may cancel their own pending_review withdrawal; funds are refunded.
     */
    public function cancel(Withdrawal $withdrawal, User $user): Withdrawal
    {
        if ($withdrawal->user_id !== $user->id) {
            throw new \LogicException('You cannot cancel another user\'s withdrawal.');
        }

        if ($withdrawal->status !== Withdrawal::STATUS_PENDING_REVIEW) {
            throw new \LogicException('Only pending review withdrawals can be cancelled.');
        }

        return DB::transaction(function () use ($withdrawal) {
            $wallet = $this->walletService->getOrCreate($withdrawal->user, $withdrawal->wallet_type);

            $this->walletService->credit(
                $wallet,
                (float) $withdrawal->amount + (float) $withdrawal->fee,
                'Withdrawal cancelled — funds returned',
                $withdrawal,
            );

            $withdrawal->update(['status' => Withdrawal::STATUS_CANCELLED]);

            activity('withdrawal')
                ->performedOn($withdrawal)
                ->causedBy($withdrawal->user)
                ->withProperties(['action' => 'withdrawal_cancelled'])
                ->log('Withdrawal cancelled');

            return $withdrawal;
        });
    }

    protected function assertEligible(User $user, string $otp): void
    {
        if (! $this->twoFactorAuth->isEnabled($user)) {
            throw new \InvalidArgumentException('Two-factor authentication is required to withdraw.');
        }

        if (! $this->twoFactorAuth->verify((string) $user->google2fa_secret, $otp)) {
            throw new \InvalidArgumentException('The 2FA code is invalid.');
        }

        if (! $user->isKycVerified()) {
            throw new \InvalidArgumentException('Your identity must be verified before you can withdraw.');
        }
    }

    protected function initialStatus(float $total): string
    {
        $autoApprove = (float) \App\Support\PlatformSettings::config('jiwa.withdrawal_auto_approve_threshold');
        $manualThreshold = (float) \App\Support\PlatformSettings::config('jiwa.withdrawal_manual_threshold');

        if ($total <= $autoApprove) {
            return Withdrawal::STATUS_APPROVED;
        }

        if ($total <= $manualThreshold) {
            return Withdrawal::STATUS_PENDING_REVIEW;
        }

        return Withdrawal::STATUS_PENDING_REVIEW;
    }
}
