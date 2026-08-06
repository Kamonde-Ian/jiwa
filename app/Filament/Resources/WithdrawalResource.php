<?php

namespace App\Filament\Resources;

use App\Domain\Withdrawals\WithdrawalService;
use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Components\Textarea;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static ?string $navigationGroup = 'Funds';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view withdrawals') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Withdrawal')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user.name')->label('User'),
                        TextEntry::make('user.email')->label('Email'),
                        TextEntry::make('wallet_type')->label('Source wallet')->badge(),
                        TextEntry::make('amount')->money('USD'),
                        TextEntry::make('fee')->money('USD'),
                        TextEntry::make('network')->badge()->color('primary'),
                        TextEntry::make('destination_address')->label('Destination')->copyable(),
                        TextEntry::make('status')->badge()->color(fn (string $state) => match ($state) {
                            Withdrawal::STATUS_COMPLETED => 'success',
                            Withdrawal::STATUS_APPROVED => 'info',
                            Withdrawal::STATUS_PENDING_REVIEW => 'warning',
                            Withdrawal::STATUS_REJECTED, Withdrawal::STATUS_CANCELLED => 'danger',
                            default => 'gray',
                        }),
                        TextEntry::make('processed_by')->label('Processed by')->formatStateUsing(fn ($state) => $state ?? '—'),
                        TextEntry::make('processed_at')->dateTime()->placeholder('—'),
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
                Tables\Columns\TextColumn::make('wallet_type')->label('Source')->badge(),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('fee')->money('USD'),
                Tables\Columns\TextColumn::make('network')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('destination_address')->limit(16)->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    Withdrawal::STATUS_COMPLETED => 'success',
                    Withdrawal::STATUS_APPROVED => 'info',
                    Withdrawal::STATUS_PENDING_REVIEW => 'warning',
                    Withdrawal::STATUS_REJECTED, Withdrawal::STATUS_CANCELLED => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('created_at')->label('Requested')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Withdrawal::STATUS_PENDING_REVIEW => 'Pending review',
                        Withdrawal::STATUS_APPROVED => 'Approved',
                        Withdrawal::STATUS_COMPLETED => 'Completed',
                        Withdrawal::STATUS_REJECTED => 'Rejected',
                        Withdrawal::STATUS_CANCELLED => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('wallet_type')->label('Source')
                    ->options([
                        'principal' => 'Principal',
                        'earnings' => 'Earnings',
                        'referral' => 'Referral',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (Withdrawal $record) => $record->status === Withdrawal::STATUS_PENDING_REVIEW
                        && auth()->user()?->can('process withdrawals'))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('note')->label('Note (optional)')->rows(2),
                    ])
                    ->action(function (Withdrawal $record, array $data, \Filament\Actions\Action $action) {
                        app(WithdrawalService::class)->approve($record, auth()->user(), $data['note'] ?? null);
                        $action->sendSuccessNotification();
                    }),
                Action::make('complete')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Withdrawal $record) => $record->status === Withdrawal::STATUS_APPROVED
                        && auth()->user()?->can('process withdrawals'))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('note')->label('Note (optional)')->rows(2),
                    ])
                    ->action(function (Withdrawal $record, array $data, \Filament\Actions\Action $action) {
                        app(WithdrawalService::class)->complete($record, auth()->user(), $data['note'] ?? null);
                        $action->sendSuccessNotification();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Withdrawal $record) => in_array($record->status, [Withdrawal::STATUS_PENDING_REVIEW, Withdrawal::STATUS_APPROVED], true)
                        && auth()->user()?->can('process withdrawals'))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')->label('Reason (funds are returned)')->required()->rows(2),
                    ])
                    ->action(function (Withdrawal $record, array $data, \Filament\Actions\Action $action) {
                        app(WithdrawalService::class)->reject($record, auth()->user(), $data['reason'] ?? null);
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
            'index' => Pages\ListWithdrawals::route('/'),
            'view' => Pages\ViewWithdrawal::route('/{record}'),
        ];
    }
}
