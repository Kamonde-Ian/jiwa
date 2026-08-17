<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Domain\Trading\TradingBotService;
use App\Domain\Wallets\WalletService;
use App\Models\PoolAllocation;
use App\Models\Wallet;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TradingRelationManager extends RelationManager
{
    protected static string $relationship = 'poolAllocations';

    protected static ?string $title = 'Bot Fund';

    public function table(Table $table): Table
    {
        return $table
            ->header(fn (): \Illuminate\Contracts\View\View => view('filament.trading-overview', [
                'user' => $this->getOwnerRecord(),
            ]))
            ->recordTitle(fn (PoolAllocation $record) => '#'.$record->id)
            ->columns([
                Tables\Columns\TextColumn::make('pool.name')
                    ->label('Fund')
                    ->searchable()
                    ->grow(),
                Tables\Columns\TextColumn::make('units')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('settled_amount')
                    ->label('Principal in fund')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('market_value')
                    ->label('Current value')
                    ->state(fn (PoolAllocation $record) => $record->currentValue())
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pnl')
                    ->label('P&L')
                    ->state(fn (PoolAllocation $record) => $record->currentValue() - (float) $record->settled_amount)
                    ->money('USD')
                    ->color(fn ($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        PoolAllocation::STATUS_ACTIVE => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('allocated_at')
                    ->label('Allocated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('allocated_at', 'desc')
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}