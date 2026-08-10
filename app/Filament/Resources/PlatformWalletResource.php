<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatformWalletResource\Pages;
use App\Models\PlatformWallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlatformWalletResource extends Resource
{
    protected static ?string $model = PlatformWallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view platform wallets') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage platform wallets') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage platform wallets') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('manage platform wallets') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Wallet')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->options([
                                PlatformWallet::TYPE_DEPOSIT => 'Deposits',
                                PlatformWallet::TYPE_WITHDRAWAL => 'Withdrawals',
                            ])
                            ->required(),
                        Forms\Components\Select::make('network')
                            ->options(collect(config('jiwa.networks'))->mapWithKeys(fn ($network, $key) => [$key => $network['name']])->all())
                            ->default('usdt_trc20')
                            ->required(),
                        Forms\Components\TextInput::make('address')
                            ->label('Address')
                            ->extraInputAttributes(['style' => 'font-family: monospace;'])
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('balance')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step('0.00000001'),
                        Forms\Components\TextInput::make('gas_balance')
                            ->label('Gas balance (transaction fees)')
                            ->helperText('Crypto reserved to cover network transaction fees.')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step('0.00000001'),
                        Forms\Components\TextInput::make('currency')
                            ->default('USD')
                            ->maxLength(10),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        PlatformWallet::TYPE_DEPOSIT => 'success',
                        PlatformWallet::TYPE_WITHDRAWAL => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('network')
                    ->formatStateUsing(fn (string $state) => config("jiwa.networks.{$state}.name") ?? $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->copyable()
                    ->copyableState(fn (string $state) => $state)
                    ->fontFamily('monospace')
                    ->limit(24)
                    ->tooltip(fn ($record) => $record->address),
                Tables\Columns\TextColumn::make('balance')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('gas_balance')
                    ->label('Gas')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        PlatformWallet::TYPE_DEPOSIT => 'Deposits',
                        PlatformWallet::TYPE_WITHDRAWAL => 'Withdrawals',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlatformWallets::route('/'),
            'create' => Pages\CreatePlatformWallet::route('/create'),
            'edit' => Pages\EditPlatformWallet::route('/{record}/edit'),
        ];
    }
}
