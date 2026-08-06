<?php

namespace App\Filament\Pages;

use App\Domain\Investments\InvestmentService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Operations extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.operations';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage settings') ?? false;
    }

    public function creditInterest(): void
    {
        $credited = app(InvestmentService::class)->creditDailyInterest();

        activity('maintenance')
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'interest_credit_manual', 'count' => $credited])
            ->log('Daily interest credited manually');

        Notification::make()
            ->title('Interest credited')
            ->body("Credited interest to {$credited} investment(s).")
            ->success()
            ->send();
    }

    public function processMaturities(): void
    {
        $matured = app(InvestmentService::class)->processMaturities();

        activity('maintenance')
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'maturities_manual', 'count' => $matured])
            ->log('Investment maturities processed manually');

        Notification::make()
            ->title('Maturities processed')
            ->body("Released principal on {$matured} matured investment(s).")
            ->success()
            ->send();
    }

    public function runAll(): void
    {
        $this->creditInterest();
        $this->processMaturities();
    }
}
