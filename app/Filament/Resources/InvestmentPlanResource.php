<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestmentPlanResource\Pages;
use App\Models\InvestmentPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvestmentPlanResource extends Resource
{
    protected static ?string $model = InvestmentPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Investments';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view investment plans') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage investment plans') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage investment plans') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('manage investment plans') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('duration_days')
                            ->label('Duration (days)')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Forms\Components\TextInput::make('daily_rate')
                            ->label('Daily rate (decimal, e.g. 0.005 = 0.5%)')
                            ->numeric()
                            ->required()
                            ->minValue(0.000001)
                            ->maxValue(1),
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Minimum amount')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_custom')
                            ->label('Custom plan (admin created)')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('duration_days')->label('Days')->sortable(),
                Tables\Columns\TextColumn::make('daily_rate')
                    ->label('Daily rate')
                    ->formatStateUsing(fn ($state) => number_format((float) $state * 100, 2).'%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_amount')->money('USD')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\IconColumn::make('is_custom')->label('Custom')->boolean(),
                Tables\Columns\TextColumn::make('investments_count')
                    ->label('Investments')
                    ->counts('investments')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(fn ($record) => $record->investments()->exists()
                        ? throw new \RuntimeException('Cannot delete a plan that has investments.')
                        : null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(fn ($records) => throw_if(
                            $records->contains(fn ($r) => $r->investments()->exists()),
                            \RuntimeException::class,
                            'Cannot delete plans that have investments.',
                        )),
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
            'index' => Pages\ListInvestmentPlans::route('/'),
            'create' => Pages\CreateInvestmentPlan::route('/create'),
            'view' => Pages\ViewInvestmentPlan::route('/{record}'),
            'edit' => Pages\EditInvestmentPlan::route('/{record}/edit'),
        ];
    }
}
