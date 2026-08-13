<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TradingPoolResource\Pages;
use App\Models\TradingPool;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TradingPoolResource extends Resource
{
    protected static ?string $model = TradingPool::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view bot fund') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage bot fund') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage bot fund') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('manage bot fund') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Fund')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('symbol')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\Select::make('currency')
                            ->default('USD')
                            ->options(['USD' => 'USD'])
                            ->required(),
                        Forms\Components\TextInput::make('base_nav')
                            ->label('Base NAV (unit price at launch)')
                            ->numeric()
                            ->default(100)
                            ->step('0.01')
                            ->disabledOn('edit')
                            ->helperText('Read-only after launch; the bot engine moves NAV daily.'),
                        Forms\Components\TextInput::make('min_allocate')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step('0.01'),
                        Forms\Components\TextInput::make('max_allocate')
                            ->label('Max allocation (blank = unlimited)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->nullable(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Accepting allocations')
                            ->default(true),
                        Forms\Components\Toggle::make('is_running')
                            ->label('Bot running')
                            ->helperText('Pausing stops booking new daily sessions; existing allocations and withdrawals keep working.')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('symbol')->badge(),
                Tables\Columns\TextColumn::make('nav')
                    ->label('NAV')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('daily_return_pct')
                    ->label('Last return')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((float) $state, 2).'%')
                    ->color(fn ($state) => $state !== null && (float) $state < 0 ? 'danger' : 'success')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_units')
                    ->label('Units in pool')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_running')
                    ->label('Bot')
                    ->boolean()
                    ->tooltip(fn ($state) => $state ? 'Bot running' : 'Bot stopped')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nav_updated_at')
                    ->label('Last settled')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Accepting allocations'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\TradingPoolResource\RelationManagers\TradingSessionsRelationManager::class,
            \App\Filament\Resources\TradingPoolResource\RelationManagers\PoolAllocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTradingPools::route('/'),
            'create' => Pages\CreateTradingPool::route('/create'),
            'view' => Pages\ViewTradingPool::route('/{record}'),
            'edit' => Pages\EditTradingPool::route('/{record}/edit'),
        ];
    }
}