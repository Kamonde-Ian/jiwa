<?php

namespace App\Filament\Resources\TradingPoolResource\RelationManagers;

use App\Models\PoolAllocation;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PoolAllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'allocations';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (PoolAllocation $record) => '#'.$record->id)
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('units')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('settled_amount')
                    ->label('Settled value')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_value')
                    ->label('Market value')
                    ->state(fn (PoolAllocation $record) => $record->currentValue())
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        PoolAllocation::STATUS_ACTIVE => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('allocated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('allocated_at', 'desc')
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}