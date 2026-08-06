<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Models\WalletTransaction;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only audit trail of the ledger. Balances must never change here —
 * mutations happen exclusively through WalletService.
 */
class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view wallet transactions') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return 'Wallet Transactions';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Transaction')
                ->columns(2)
                ->schema([
                    TextEntry::make('wallet.user.name')->label('User'),
                    TextEntry::make('wallet.type')->label('Wallet')->badge(),
                    TextEntry::make('type')->badge()->color(fn (string $state) => $state === 'credit' ? 'success' : 'danger'),
                    TextEntry::make('amount')->money('USD'),
                    TextEntry::make('currency'),
                    TextEntry::make('balance_after')->money('USD'),
                    TextEntry::make('reference_type'),
                    TextEntry::make('reference_id'),
                    TextEntry::make('description'),
                    TextEntry::make('created_at')->dateTime(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wallet.user.name')->label('User')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('wallet.type')->label('Wallet')->badge()->color(fn (string $state) => match ($state) {
                    'principal' => 'primary',
                    'earnings' => 'success',
                    'referral' => 'warning',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn (string $state) => $state === 'credit' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('balance_after')->label('Balance after')->money('USD'),
                Tables\Columns\TextColumn::make('description')->limit(40),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('wallet.type')->label('Wallet')
                    ->options(['principal' => 'Principal', 'earnings' => 'Earnings', 'referral' => 'Referral']),
                SelectFilter::make('type')
                    ->options(['credit' => 'Credit', 'debit' => 'Debit']),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
            'view' => Pages\ViewWalletTransaction::route('/{record}'),
        ];
    }
}
