<?php

namespace App\Filament\Resources;

use App\Domain\Deposits\DepositService;
use App\Filament\Resources\DepositResource\Pages;
use App\Models\Deposit;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-circle';

    protected static ?string $navigationGroup = 'Funds';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view deposits') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Deposit')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user.name')->label('User'),
                        TextEntry::make('user.email')->label('Email'),
                        TextEntry::make('network')->badge()->color('primary'),
                        TextEntry::make('currency'),
                        TextEntry::make('amount_currency')->label('Amount (crypto)')->placeholder('—'),
                        TextEntry::make('amount_usd')->label('Amount (USD)')->money('USD'),
                        TextEntry::make('tx_hash')->label('Transaction hash')->copyable()->placeholder('—'),
                        TextEntry::make('status')->badge()->color(fn (string $state) => match ($state) {
                            Deposit::STATUS_CONFIRMED => 'success',
                            Deposit::STATUS_PENDING => 'warning',
                            Deposit::STATUS_REJECTED => 'danger',
                            default => 'gray',
                        }),
                        TextEntry::make('confirmed_by')->label('Confirmed by')->formatStateUsing(fn ($state) => $state ?? '—'),
                        TextEntry::make('confirmed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('admin_note')->placeholder('—'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('network')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('tx_hash')->label('Tx hash')->limit(18)->toggleable(),
                Tables\Columns\TextColumn::make('amount_usd')->label('Amount (USD)')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    Deposit::STATUS_CONFIRMED => 'success',
                    Deposit::STATUS_PENDING => 'warning',
                    Deposit::STATUS_REJECTED => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Deposit::STATUS_PENDING => 'Pending',
                        Deposit::STATUS_CONFIRMED => 'Confirmed',
                        Deposit::STATUS_REJECTED => 'Rejected',
                        Deposit::STATUS_EXPIRED => 'Expired',
                    ]),
                Tables\Filters\SelectFilter::make('network')
                    ->options(collect(config('jiwa.networks'))->mapWithKeys(fn ($n, $k) => [$k => $n['name']])->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('confirm')
                    ->label('Confirm & Credit')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Deposit $record) => $record->status === Deposit::STATUS_PENDING
                        && auth()->user()?->can('review deposits'))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('note')->label('Note (optional)')->rows(2),
                    ])
                    ->action(function (Deposit $record, array $data, \Filament\Actions\Action $action) {
                        app(DepositService::class)->confirm($record, auth()->user(), $data['note'] ?? null);
                        $action->sendSuccessNotification();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Deposit $record) => $record->status === Deposit::STATUS_PENDING
                        && auth()->user()?->can('review deposits'))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')->label('Reason (visible to user)')->required()->rows(2),
                    ])
                    ->action(function (Deposit $record, array $data, \Filament\Actions\Action $action) {
                        app(DepositService::class)->reject($record, auth()->user(), $data['reason'] ?? null);
                        $action->sendSuccessNotification();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeposits::route('/'),
            'view' => Pages\ViewDeposit::route('/{record}'),
        ];
    }
}
