<?php

namespace App\Filament\Resources;

use App\Domain\Kyc\KycService;
use App\Filament\Resources\KycVerificationResource\Pages;
use App\Models\KycVerification;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class KycVerificationResource extends Resource
{
    protected static ?string $model = KycVerification::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Verification';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'KYC Reviews';

    protected static ?string $modelLabel = 'KYC submission';

    protected static ?string $pluralModelLabel = 'KYC submissions';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('review kyc') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Applicant')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user.name')->label('Name'),
                        TextEntry::make('user.email')->label('Email'),
                        TextEntry::make('user.phone')->label('Phone')->placeholder('—'),
                        TextEntry::make('user.country')->label('Country')->placeholder('—'),
                        TextEntry::make('document_type')->label('Document type')->badge(),
                        TextEntry::make('status')->badge()->color(fn (string $state) => match ($state) {
                            KycVerification::STATUS_VERIFIED => 'success',
                            KycVerification::STATUS_PENDING => 'warning',
                            KycVerification::STATUS_REJECTED => 'danger',
                            default => 'gray',
                        }),
                        TextEntry::make('created_at')->label('Submitted')->dateTime(),
                        TextEntry::make('reviewer.name')->label('Reviewed by')->placeholder('—'),
                        TextEntry::make('reviewed_at')->label('Reviewed at')->dateTime()->placeholder('—'),
                    ]),
                Section::make('Submitted Documents')
                    ->description('Review each image carefully before approving. Documents are stored securely on the public disk.')
                    ->columns(3)
                    ->schema([
                        ImageEntry::make('document_path')
                            ->label('Document (front)')
                            ->getStateUsing(fn (KycVerification $record) => Storage::url($record->document_path))
                            ->url(fn (KycVerification $record) => Storage::url($record->document_path))
                            ->openUrlInNewTab(),
                        ImageEntry::make('document_back_path')
                            ->label('Document (back)')
                            ->getStateUsing(fn (KycVerification $record) => Storage::url($record->document_back_path))
                            ->url(fn (KycVerification $record) => Storage::url($record->document_back_path))
                            ->openUrlInNewTab(),
                        ImageEntry::make('selfie_path')
                            ->label('Selfie')
                            ->getStateUsing(fn (KycVerification $record) => Storage::url($record->selfie_path))
                            ->url(fn (KycVerification $record) => Storage::url($record->selfie_path))
                            ->openUrlInNewTab(),
                    ]),
                Section::make('Decision')
                    ->visible(fn (KycVerification $record) => $record->status !== KycVerification::STATUS_PENDING)
                    ->schema([
                        TextEntry::make('rejection_reason')->label('Rejection reason')->placeholder('—')->color('danger'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.email')->label('Email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('document_type')->label('Document')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    KycVerification::STATUS_VERIFIED => 'success',
                    KycVerification::STATUS_PENDING => 'warning',
                    KycVerification::STATUS_REJECTED => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        KycVerification::STATUS_PENDING => 'Pending',
                        KycVerification::STATUS_VERIFIED => 'Verified',
                        KycVerification::STATUS_REJECTED => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('document_type')
                    ->options([
                        'government_id' => 'Government ID',
                        'passport' => 'Passport',
                        'drivers_license' => "Driver's license",
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (KycVerification $record) => $record->status === KycVerification::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->modalHeading('Approve KYC submission')
                    ->modalDescription('Mark this identity as verified. The user will be able to invest and withdraw.')
                    ->form([
                        Textarea::make('note')->label('Note (optional)')->rows(2),
                    ])
                    ->action(function (KycVerification $record, array $data, Action $action) {
                        app(KycService::class)->approve($record, auth()->user(), $data['note'] ?? null);
                        $action->sendSuccessNotification();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (KycVerification $record) => $record->status === KycVerification::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->modalHeading('Reject KYC submission')
                    ->form([
                        Textarea::make('reason')->label('Reason (visible to user)')->required()->rows(3),
                    ])
                    ->action(function (KycVerification $record, array $data, Action $action) {
                        app(KycService::class)->reject($record, auth()->user(), $data['reason']);
                        $action->sendSuccessNotification();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKycVerifications::route('/'),
            'view' => Pages\ViewKycVerification::route('/{record}'),
        ];
    }
}
