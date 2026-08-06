<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WithdrawalsRelationManager extends RelationManager
{
    protected static string $relationship = 'withdrawals';

    protected static ?string $title = 'Withdrawals';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('destination_address')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('wallet_type')->label('Source')->badge(),
                Tables\Columns\TextColumn::make('amount')->money('USD'),
                Tables\Columns\TextColumn::make('network')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'completed' => 'success',
                    'approved' => 'info',
                    'pending_review' => 'warning',
                    'rejected', 'cancelled' => 'danger',
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
