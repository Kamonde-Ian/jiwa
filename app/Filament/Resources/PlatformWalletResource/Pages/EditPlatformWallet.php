<?php

namespace App\Filament\Resources\PlatformWalletResource\Pages;

use App\Filament\Resources\PlatformWalletResource;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditPlatformWallet extends EditRecord
{
    protected static string $resource = PlatformWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->form([
                    TextInput::make('phrase')
                        ->label('Wallet address phrase')
                        ->password()
                        ->required(),
                ])
                ->action(function (Actions\DeleteAction $action, array $data) {
                    if (! $this->record->verifyPhrase($data['phrase'] ?? '')) {
                        Notification::make()
                            ->danger()
                            ->title('Invalid wallet phrase')
                            ->body('The phrase entered does not match this wallet.')
                            ->send();
                        $action->halt();

                        return;
                    }

                    $this->record->delete();

                    Notification::make()
                        ->success()
                        ->title('Wallet deleted')
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    /**
     * Saving changes to a wallet requires the wallet's phrase to be entered.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $phrase = $data['phrase_confirmation'] ?? '';
        unset($data['phrase_confirmation']);

        if (! $this->record->verifyPhrase($phrase)) {
            throw ValidationException::withMessages([
                'data.phrase_confirmation' => 'The wallet phrase does not match this wallet.',
            ]);
        }

        return $data;
    }
}
