<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DepositsRelationManager extends RelationManager
{
    protected static string $relationship = 'deposits';

    protected static ?string $title = 'Deposits';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tx_hash')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('network')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('amount_usd')->label('Amount (USD)')->money('USD'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'confirmed' => 'success',
                    'pending' => 'warning',
                    'rejected' => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
