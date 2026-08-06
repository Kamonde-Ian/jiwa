<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvestmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'investments';

    protected static ?string $title = 'Investments';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('plan.name')
            ->columns([
                Tables\Columns\TextColumn::make('plan.name')->label('Plan'),
                Tables\Columns\TextColumn::make('principal_amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('daily_rate_snapshot')->label('Daily rate')->suffix('%')
                    ->formatStateUsing(fn ($state) => number_format((float) $state * 100, 2)),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'active' => 'success',
                    'matured' => 'info',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('matures_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'matured' => 'Matured', 'cancelled' => 'Cancelled']),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
