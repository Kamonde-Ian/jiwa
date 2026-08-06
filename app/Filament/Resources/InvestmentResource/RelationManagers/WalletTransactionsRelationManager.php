<?php

namespace App\Filament\Resources\InvestmentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Ledger Entries';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->inverseRelationship('reference')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('wallet.user.name')->label('User'),
                Tables\Columns\TextColumn::make('wallet.type')->label('Wallet')->badge(),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn (string $state) => match ($state) {
                    'credit' => 'success',
                    'debit' => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('balance_after')->label('Balance after')->money('USD'),
                Tables\Columns\TextColumn::make('description')->limit(50),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }
}
