<?php

namespace App\Domain\Kyc;

use App\Models\KycVerification;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class KycService
{
    /**
     * Approve a pending KYC submission and mark the user as identity verified.
     */
    public function approve(KycVerification $submission, User $admin, ?string $note = null): KycVerification
    {
        if ($submission->status !== KycVerification::STATUS_PENDING) {
            throw new \LogicException('Only pending KYC submissions can be approved.');
        }

        return DB::transaction(function () use ($submission, $admin) {
            $user = $submission->user;

            $submission->update([
                'status' => KycVerification::STATUS_VERIFIED,
                'rejection_reason' => null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $user->update(['kyc_status' => User::KYC_VERIFIED]);

            activity('kyc')
                ->performedOn($submission)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'kyc_approved',
                    'user' => $user->email,
                    'submission_id' => $submission->id,
                ])
                ->log('KYC application approved');

            $this->notifyUser($user, 'Your identity has been verified. You can now invest and withdraw.');

            return $submission;
        });
    }

    /**
     * Reject a pending KYC submission with a visible reason.
     */
    public function reject(KycVerification $submission, User $admin, string $reason): KycVerification
    {
        if ($submission->status !== KycVerification::STATUS_PENDING) {
            throw new \LogicException('Only pending KYC submissions can be rejected.');
        }

        return DB::transaction(function () use ($submission, $admin, $reason) {
            $user = $submission->user;

            $submission->update([
                'status' => KycVerification::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $user->update(['kyc_status' => User::KYC_REJECTED]);

            activity('kyc')
                ->performedOn($submission)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'kyc_rejected',
                    'user' => $user->email,
                    'submission_id' => $submission->id,
                    'reason' => $reason,
                ])
                ->log('KYC application rejected');

            $this->notifyUser($user, 'Your identity verification was rejected. Please review the reason and resubmit.');

            return $submission;
        });
    }

    protected function notifyUser(User $user, string $message): void
    {
        Notification::make()
            ->title('KYC status updated')
            ->body($message)
            ->success()
            ->sendToDatabase($user);
    }
}
