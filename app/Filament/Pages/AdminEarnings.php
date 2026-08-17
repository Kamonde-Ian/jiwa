<?php

namespace App\Filament\Pages;

use App\Domain\AdminEarnings\AdminEarningsService;
use App\Models\AdminEarning;
use App\Models\User;
use App\Models\Wallet;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AdminEarnings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.admin-earnings';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view admin earnings') ?? false;
    }

    public function service(): AdminEarningsService
    {
        return app(AdminEarningsService::class);
    }

    /**
     * @return Collection<int, User>
     */
    public function admins(): Collection
    {
        return $this->service()->admins();
    }

    public function adminBalance(User $admin): float
    {
        return (float) $admin->wallets()
            ->where('type', Wallet::TYPE_ADMIN_EARNINGS)
            ->first()?->balance;
    }

    /**
     * @return Collection<int, AdminEarning>
     */
    public function recentEarnings(): Collection
    {
        return AdminEarning::query()
            ->with(['admin', 'source'])
            ->latest()
            ->limit(25)
            ->get();
    }

    public function rate(): float
    {
        return $this->service()->rate();
    }

    public function enabled(): bool
    {
        return $this->service()->enabled();
    }

    public function distributeNow(): void
    {
        $result = $this->service()->distributePending();

        activity('admin_earnings')
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'admin_earnings_manual_run'] + $result)
            ->log('Admin earnings distribution run manually');

        Notification::make()
            ->title('Admin earnings distributed')
            ->body('$' . number_format($result['total'], 2) . " split among {$result['admins']} admin(s).")
            ->success()
            ->send();
    }
}