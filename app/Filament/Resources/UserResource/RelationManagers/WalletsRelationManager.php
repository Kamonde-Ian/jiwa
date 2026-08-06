<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WalletsRelationManager extends RelationManager
{
    protected static string $relationship = 'wallets';

    protected static ?string $title = 'Wallets';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')->badge()->color(fn (string $state) => match ($state) {
                    'principal' => 'primary',
                    'earnings' => 'success',
                    'referral' => 'warning',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('currency'),
                Tables\Columns\TextColumn::make('balance')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Last updated')->dateTime(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
