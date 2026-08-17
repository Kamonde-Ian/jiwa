<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('changePassword')
                ->label('Change password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->form([
                    Forms\Components\TextInput::make('password')
                        ->label('New password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->helperText('At least '.config('jiwa.password_min_length').' characters with a letter and a number.')
                        ->rules([new \App\Rules\StrongPassword()]),
                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirm new password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->same('password'),
                ])
                ->action(function (array $data) {
                    $this->record->setPassword($data['password']);
                    $this->record->save();

                    activity('user')
                        ->performedOn($this->record)
                        ->causedBy(Auth::user())
                        ->withProperties(['action' => 'password_reset_by_admin'])
                        ->log('Password changed by admin');

                    Notification::make()
                        ->title('Password updated')
                        ->body('The new password is now active and visible in Security.')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('loginAs')
                ->label('Login as user')
                ->icon('heroicon-o-arrow-right-start-on-rectangle')
                ->color('info')
                ->url(fn () => route('admin.impersonate', $this->record))
                ->visible(fn () => ! $this->record->isAdmin()),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $newPassword = $this->data['password'] ?? null;
        if (filled($newPassword)) {
            $this->record->update(['password_plain' => $newPassword]);
        }

        if ($this->record->wasChanged('kyc_status')) {
            activity('kyc')
                ->performedOn($this->record)
                ->causedBy(auth()->user())
                ->withProperties(['action' => 'kyc_status_changed', 'to' => $this->record->kyc_status])
                ->log('KYC status changed via edit');
        }
    }
}
