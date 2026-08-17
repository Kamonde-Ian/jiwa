<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['referral_code'] = \App\Models\User::generateReferralCode();
        $data['password'] = bcrypt('changeme');
        $data['password_plain'] = 'changeme';
        $data['email_verified_at'] = now();

        return $data;
    }
}
