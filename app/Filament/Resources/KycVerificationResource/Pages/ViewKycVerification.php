<?php

namespace App\Filament\Resources\KycVerificationResource\Pages;

use App\Domain\Kyc\KycService;
use App\Filament\Resources\KycVerificationResource;
use App\Models\KycVerification;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewKycVerification extends ViewRecord
{
    protected static string $resource = KycVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === KycVerification::STATUS_PENDING)
                ->requiresConfirmation()
                ->modalDescription('Mark this identity as verified. The user will be able to invest and withdraw.')
                ->form([
                    Textarea::make('note')->label('Note (optional)')->rows(2),
                ])
                ->action(function (Actions\Action $action, array $data) {
                    app(KycService::class)->approve($this->record, auth()->user(), $data['note'] ?? null);
                    $action->sendSuccessNotification();
                }),
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === KycVerification::STATUS_PENDING)
                ->requiresConfirmation()
                ->modalDescription('Rejecting notifies the user and returns their KYC status to unverified.')
                ->form([
                    Textarea::make('reason')->label('Reason (visible to user)')->required()->rows(3),
                ])
                ->action(function (Actions\Action $action, array $data) {
                    app(KycService::class)->reject($this->record, auth()->user(), $data['reason']);
                    $action->sendSuccessNotification();
                }),
        ];
    }
}
