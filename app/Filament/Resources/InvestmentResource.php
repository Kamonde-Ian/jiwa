<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestmentResource\Pages;
use App\Filament\Resources\InvestmentResource\RelationManagers\WalletTransactionsRelationManager;
use App\Models\Investment;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvestmentResource extends Resource
{
    protected static ?string $model = Investment::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationGroup = 'Investments';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view investments') ?? false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Investment')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user.name')->label('User'),
                        TextEntry::make('user.email')->label('Email'),
                        TextEntry::make('plan.name')->label('Plan'),
                        TextEntry::make('principal_amount')->label('Principal')->money('USD'),
                        TextEntry::make('daily_rate_snapshot')
                            ->label('Daily rate')
                            ->formatStateUsing(fn ($state) => number_format((float) $state * 100, 2).'%'),
                        TextEntry::make('status')->badge()->color(fn (string $state) => match ($state) {
                            Investment::STATUS_ACTIVE => 'success',
                            Investment::STATUS_MATURED => 'info',
                            default => 'gray',
                        }),
                        TextEntry::make('starts_at')->dateTime(),
                        TextEntry::make('matures_at')->dateTime(),
                        TextEntry::make('last_interest_credited_at')
                            ->label('Last interest credit')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('plan.name')->label('Plan')->sortable(),
                Tables\Columns\TextColumn::make('principal_amount')->label('Principal')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('daily_rate_snapshot')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state) => number_format((float) $state * 100, 2).'%'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    Investment::STATUS_ACTIVE => 'success',
                    Investment::STATUS_MATURED => 'info',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('matures_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('last_interest_credited_at')
                    ->label('Last interest')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Investment::STATUS_ACTIVE => 'Active',
                        Investment::STATUS_MATURED => 'Matured',
                        Investment::STATUS_CANCELLED => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('plan')
                    ->relationship('plan', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            WalletTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestments::route('/'),
            'view' => Pages\ViewInvestment::route('/{record}'),
        ];
    }
}
