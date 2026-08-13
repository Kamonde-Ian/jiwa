<?php

namespace App\Filament\Pages;

use App\Domain\Investments\InvestmentService;
use App\Domain\Trading\TradingBotService;
use App\Models\TradingPool;
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

    public function botPool(): TradingPool
    {
        return app(TradingBotService::class)->pool();
    }

    public function runBot(): void
    {
        $result = app(TradingBotService::class)->runDailyCycle();

        activity('maintenance')
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'bot_cycle_manual'] + $result)
            ->log('Bot trading cycle run manually');

        Notification::make()
            ->title('Bot cycle complete')
            ->body("Settled {$result['settled']} session(s), {$result['paid']} payout(s), skipped {$result['paused']} stopped pool(s).")
            ->success()
            ->send();
    }

    public function startBot(): void
    {
        app(TradingBotService::class)->setRunning($this->botPool(), true);

        Notification::make()->title('Bot started')->success()->send();
    }

    public function stopBot(): void
    {
        app(TradingBotService::class)->setRunning($this->botPool(), false);

        Notification::make()
            ->title('Bot stopped')
            ->body('No new sessions will be booked until it is restarted.')
            ->warning()
            ->send();
    }
}
