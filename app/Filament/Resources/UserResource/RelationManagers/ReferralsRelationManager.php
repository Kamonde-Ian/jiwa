<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralsRelationManager extends RelationManager
{
    protected static string $relationship = 'referrals';

    protected static ?string $title = 'Referral Tree';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('referral_code')->label('Code'),
                Tables\Columns\TextColumn::make('created_at')->label('Signed up')->dateTime()->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
