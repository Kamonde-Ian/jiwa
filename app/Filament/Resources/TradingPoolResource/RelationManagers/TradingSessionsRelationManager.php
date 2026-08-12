<?php

namespace App\Filament\Resources\TradingPoolResource\RelationManagers;

use App\Models\TradingSession;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TradingSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (TradingSession $record) => $record->session_date->format('M j, Y'))
            ->columns([
                Tables\Columns\TextColumn::make('session_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('open_nav')->label('Open')->money('USD'),
                Tables\Columns\TextColumn::make('high_nav')->label('High')->money('USD'),
                Tables\Columns\TextColumn::make('low_nav')->label('Low')->money('USD'),
                Tables\Columns\TextColumn::make('close_nav')->label('Close')->money('USD'),
                Tables\Columns\TextColumn::make('return_pct')
                    ->label('Result')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2).'%')
                    ->color(fn ($state) => (float) $state < 0 ? 'danger' : 'success')
                    ->badge(),
                Tables\Columns\TextColumn::make('trade_count')->label('Trades')->sortable(),
                Tables\Columns\TextColumn::make('pnl')->label('Pool P&L')->money('USD')->sortable(),
            ])
            ->defaultSort('session_date', 'desc')
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}