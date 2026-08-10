<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DepositResource;
use App\Filament\Resources\KycVerificationResource;
use App\Filament\Resources\WithdrawalResource;
use App\Models\Deposit;
use App\Models\KycVerification;
use App\Models\Withdrawal;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Model;

class PendingReviewsWidget extends TableWidget
{
    use InteractsWithTable;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pending Reviews';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn () => Deposit::query()
                    ->where('status', Deposit::STATUS_PENDING)
                    ->with('user')
                    ->selectRaw("'deposit' as kind, id, user_id, network, amount_usd as amount, created_at, null as document_type")
                    ->union(
                        Withdrawal::query()
                            ->whereIn('status', [Withdrawal::STATUS_PENDING_REVIEW, Withdrawal::STATUS_APPROVED])
                            ->with('user')
                            ->selectRaw("'withdrawal' as kind, id, user_id, network, amount, created_at, null as document_type"),
                    )
                    ->union(
                        KycVerification::query()
                            ->where('status', KycVerification::STATUS_PENDING)
                            ->with('user')
                            ->selectRaw("'kyc' as kind, id, user_id, document_type, null as amount, created_at, document_type"),
                    ),
            )
            ->columns([
                TextColumn::make('kind')->badge()->color(fn (string $state) => match ($state) {
                    'deposit' => 'success',
                    'withdrawal' => 'danger',
                    default => 'primary',
                }),
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('network')
                    ->label('Network')
                    ->badge()
                    ->color('primary')
                    ->placeholder('')
                    ->formatStateUsing(fn ($state, Model $record) => $record->kind === 'kyc' ? '' : strtoupper($state)),
                TextColumn::make('document_type')
                    ->label('Document')
                    ->badge()
                    ->color('primary')
                    ->placeholder('')
                    ->formatStateUsing(fn ($state, Model $record) => $record->kind === 'kyc' ? strtoupper($state) : ''),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->placeholder('')
                    ->formatStateUsing(fn ($state, Model $record) => $record->kind === 'kyc' ? '' : '$'.number_format((float) $state, 2)),
                TextColumn::make('created_at')->label('Requested')->dateTime(),
            ])
            ->recordUrl(fn (Model $record) => match ($record->kind) {
                'deposit' => DepositResource::getUrl('view', ['record' => $record->id]),
                'withdrawal' => WithdrawalResource::getUrl('view', ['record' => $record->id]),
                default => KycVerificationResource::getUrl('view', ['record' => $record->id]),
            })
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
}
