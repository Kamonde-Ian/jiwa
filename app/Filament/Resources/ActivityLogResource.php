<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Infolists\Components\KeyValue;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'Activity Log';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage roles') ?? false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Activity')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('description'),
                        TextEntry::make('log_name')->badge(),
                        TextEntry::make('event')->placeholder('—'),
                        TextEntry::make('causer.name')->label('Caused by')->placeholder('—'),
                        TextEntry::make('subject_type')->label('Subject'),
                        TextEntry::make('subject_id')->label('Subject ID'),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
                Section::make('Properties')
                    ->schema([
                        KeyValue::make('properties')
                            ->schema([])
                            ->keyLabel('Key')
                            ->valueLabel('Value'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('log_name')->badge(),
                Tables\Columns\TextColumn::make('description')->limit(60)->searchable(),
                Tables\Columns\TextColumn::make('causer.name')->label('Caused by')->placeholder('System'),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state) => class_basename((string) $state)),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->options([
                        'auth' => 'Auth',
                        'wallet' => 'Wallet',
                        'investment' => 'Investment',
                        'interest' => 'Interest',
                        'deposit' => 'Deposit',
                        'withdrawal' => 'Withdrawal',
                        'referral' => 'Referral',
                        'security' => 'Security',
                        'kyc' => 'KYC',
                        'settings' => 'Settings',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
