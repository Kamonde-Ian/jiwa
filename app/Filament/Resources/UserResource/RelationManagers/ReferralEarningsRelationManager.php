<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralEarningsRelationManager extends RelationManager
{
    protected static string $relationship = 'referralEarnings';

    protected static ?string $title = 'Referral Earnings';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                Tables\Columns\TextColumn::make('referredUser.name')->label('Referred user'),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('rate')->label('Rate')->formatStateUsing(fn ($state) => number_format((float) $state * 100, 1).'%'),
                Tables\Columns\TextColumn::make('investment_id')->label('Investment ID')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }
}
