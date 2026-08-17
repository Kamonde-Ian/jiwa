<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\DepositsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\InvestmentsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\ReferralEarningsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\ReferralsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\TradingRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\WalletsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\WithdrawalsRelationManager;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Users';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Users';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view users') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage users') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage users') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation) => $operation === 'create')
                            ->helperText('At least '.config('jiwa.password_min_length').' characters with a letter and a number.')
                            ->rules([new \App\Rules\StrongPassword()]),
                        Forms\Components\TextInput::make('phone')->tel()->maxLength(50),
                        Forms\Components\TextInput::make('country')->maxLength(255),
                        Forms\Components\TextInput::make('referral_code')->disabled()->dehydrated(),
                        Forms\Components\Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload(),
                        Forms\Components\Select::make('kyc_status')
                            ->options([
                                User::KYC_UNVERIFIED => 'Unverified',
                                User::KYC_PENDING => 'Pending',
                                User::KYC_VERIFIED => 'Verified',
                                User::KYC_REJECTED => 'Rejected',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Account')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('phone')->placeholder('—'),
                        TextEntry::make('country')->placeholder('—'),
                        TextEntry::make('referral_code')->placeholder('—'),
                        TextEntry::make('referredBy.email')
                            ->label('Referred by')
                            ->placeholder('—'),
                        TextEntry::make('kyc_status')->badge()->color(fn (string $state) => match ($state) {
                            User::KYC_VERIFIED => 'success',
                            User::KYC_PENDING => 'warning',
                            User::KYC_REJECTED => 'danger',
                            default => 'gray',
                        }),
                        TextEntry::make('two_factor_enabled')->label('2FA')->badge()->state(fn ($record) => $record->two_factor_enabled ? 'Enabled' : 'Disabled'),
                        TextEntry::make('email_verified_at')->label('Email verified')->dateTime()->placeholder('—'),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
                Section::make('Security')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('password_plain')
                            ->label('Current password')
                            ->copyable()
                            ->placeholder('— not recorded (set before plaintext copy was enabled)')
                            ->color(fn ($state) => $state ? 'warning' : 'gray'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('referral_code')->searchable()->toggleable(),
                Tables\Columns\IconColumn::make('two_factor_enabled')->label('2FA')->boolean(),
                Tables\Columns\TextColumn::make('kyc_status')->badge()->color(fn (string $state) => match ($state) {
                    User::KYC_VERIFIED => 'success',
                    User::KYC_PENDING => 'warning',
                    User::KYC_REJECTED => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('created_at')->label('Joined')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kyc_status')
                    ->options([
                        User::KYC_UNVERIFIED => 'Unverified',
                        User::KYC_PENDING => 'Pending',
                        User::KYC_VERIFIED => 'Verified',
                        User::KYC_REJECTED => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('two_factor_enabled')->label('2FA')
                    ->options([true => 'Enabled', false => 'Disabled'])
                    ->query(fn (Builder $query, $state) => $query->where('two_factor_enabled', $state['value'] ?? false)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('loginAs')
                    ->label('Login as user')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->color('info')
                    ->url(fn (User $record) => route('admin.impersonate', $record))
                    ->visible(fn (User $record) => ! $record->isAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_kyc')
                        ->label('Approve KYC')
                        ->icon('heroicon-o-shield-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => self::bulkSetKyc($records, User::KYC_VERIFIED)),
                    Tables\Actions\BulkAction::make('reject_kyc')
                        ->label('Reject KYC')
                        ->icon('heroicon-o-shield-exclamation')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => self::bulkSetKyc($records, User::KYC_REJECTED)),
                ]),
            ]);
    }

    protected static function bulkSetKyc($records, string $status): void
    {
        foreach ($records as $user) {
            $user->update(['kyc_status' => $status]);

            activity('kyc')
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['action' => 'kyc_status_changed', 'to' => $status])
                ->log("KYC status set to {$status}");
        }
    }

    public static function getRelations(): array
    {
        return [
            TradingRelationManager::class,
            WalletsRelationManager::class,
            InvestmentsRelationManager::class,
            ReferralsRelationManager::class,
            ReferralEarningsRelationManager::class,
            DepositsRelationManager::class,
            WithdrawalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
