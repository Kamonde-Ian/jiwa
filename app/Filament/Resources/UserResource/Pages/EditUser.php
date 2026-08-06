<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('kyc_status')) {
            activity('kyc')
                ->performedOn($this->record)
                ->causedBy(auth()->user())
                ->withProperties(['action' => 'kyc_status_changed', 'to' => $this->record->kyc_status])
                ->log('KYC status changed via edit');
        }
    }
}
