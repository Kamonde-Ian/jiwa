<?php

namespace App\Livewire;

use App\Domain\Trading\TradingBotService;
use App\Domain\Wallets\WalletService;
use App\Models\PoolAllocation;
use App\Models\Wallet;
use App\Support\PlatformSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.dashboard')]
#[Title('Trade')]
class Trade extends Component
{
    public string $panel = 'allocate';

    public ?float $allocateAmount = null;

    public ?float $withdrawAmount = null;

    public string $pair = 'BTC/USDT';

    public string $timeframe = '5m';

    public function mount(TradingBotService $service): void
    {
        if (! PlatformSettings::config('jiwa.trading_enabled')) {
            abort(404);
        }

        $service->pool();

        $this->pair = (string) PlatformSettings::config('jiwa.trading_default_pair');
        $this->timeframe = (string) PlatformSettings::config('jiwa.trading_default_timeframe');
    }

    public function setPair(string $pair): void
    {
        $symbols = collect(PlatformSettings::config('jiwa.trading_pairs'))->pluck('symbol')->all();

        if (in_array($pair, $symbols, true)) {
            $this->pair = $pair;
        }
    }

    public function setTimeframe(string $timeframe): void
    {
        if (in_array($timeframe, PlatformSettings::config('jiwa.trading_timeframes'), true)) {
            $this->timeframe = $timeframe;
        }
    }

    public function startBot(TradingBotService $service): void
    {
        $service->setRunning($service->pool(), true);
    }

    public function stopBot(TradingBotService $service): void
    {
        $service->setRunning($service->pool(), false);
    }

    public function setPanel(string $panel): void
    {
        $this->panel = in_array($panel, ['allocate', 'withdraw'], true) ? $panel : 'allocate';
    }

    public function setAllocatePercent(float $percent, WalletService $walletService): void
    {
        $principal = (float) $walletService->getOrCreate(auth()->user(), Wallet::TYPE_PRINCIPAL)->balance;
        $this->allocateAmount = round($principal * $percent / 100, 2);
    }

    public function setWithdrawPercent(float $percent, TradingBotService $service): void
    {
        $user = auth()->user();

        $available = $user->poolAllocations()
            ->where('status', PoolAllocation::STATUS_ACTIVE)
            ->get()
            ->reduce(fn ($carry, $a) => $carry + $a->currentValue((float) $service->pool()->nav), 0.0);

        $this->withdrawAmount = round($available * $percent / 100, 2);
    }

    public function allocate(TradingBotService $service)
    {
        $this->validate(['allocateAmount' => ['required', 'numeric', 'gt:0']]);

        try {
            $allocation = $service->allocate(auth()->user(), $service->pool(), (float) $this->allocateAmount);

            session()->flash('trade', [
                'kind' => 'allocated',
                'amount' => (float) $this->allocateAmount,
                'nav' => (float) $service->pool()->nav,
                'units' => (float) $allocation->units,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->addError('allocateAmount', $e->getMessage());

            return;
        }

        $this->reset('allocateAmount');
    }

    public function withdraw(TradingBotService $service)
    {
        $this->validate(['withdrawAmount' => ['required', 'numeric', 'gt:0']]);

        try {
            $service->withdraw(auth()->user(), $service->pool(), (float) $this->withdrawAmount);

            session()->flash('trade', [
                'kind' => 'withdrawn',
                'amount' => (float) $this->withdrawAmount,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->addError('withdrawAmount', $e->getMessage());

            return;
        }

        $this->reset('withdrawAmount');
    }

    public function render(TradingBotService $service, WalletService $walletService)
    {
        $user = auth()->user();
        $pool = $service->pool();
        $nav = (float) $pool->nav;
        $botRunning = (bool) $pool->is_running;

        $pairs = collect(PlatformSettings::config('jiwa.trading_pairs'))
            ->map(fn (array $p) => [
                'symbol' => $p['symbol'],
                'label' => $p['label'] ?? $p['symbol'],
                'base' => $p['base'] ?? '',
                'quote' => $p['quote'] ?? '',
            ])
            ->values()
            ->all();

        $timeframes = PlatformSettings::config('jiwa.trading_timeframes') ?: [];

        $allocations = $user->poolAllocations()
            ->where('status', PoolAllocation::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();

        $positionValue = $allocations->reduce(fn ($c, $a) => $c + $a->currentValue($nav), 0.0);
        $units = $allocations->sum('units');

        $earnings = $walletService->getOrCreate($user, Wallet::TYPE_EARNINGS);
        $principal = $walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL);
        $referral = $walletService->getOrCreate($user, Wallet::TYPE_REFERRAL);

        // Ledger-driven money tracking so the headline numbers always
        // reconcile with the wallet statements.
        $invested = (float) $principal->transactions()
            ->where('type', 'debit')
            ->where('description', 'like', 'Allocated to%')
            ->sum('amount');

        $withdrawnFromPool = (float) $earnings->transactions()
            ->where('type', 'credit')
            ->where('description', 'like', 'Bot fund withdrawal%')
            ->sum('amount');

        $returnsCredited = (float) $earnings->transactions()
            ->where('type', 'credit')
            ->where('description', 'like', 'Bot trading result%')
            ->sum('amount');

        $netInPool = $invested - $withdrawnFromPool;
        $unrealized = $positionValue - $netInPool;
        $totalReturn = $returnsCredited + $unrealized;
        $returnPct = $invested > 0 ? ($totalReturn / $invested) * 100 : 0.0;

        $today = $service->today($pool);
        $todayPnl = $positionValue * ($today['change_pct'] / 100);

        $sessions = $pool->sessions()
            ->orderByDesc('session_date')
            ->limit(20)
            ->get();

        $allocationIds = $pool->allocations()
            ->where('user_id', $user->id)
            ->pluck('id');

        $dailyPayouts = $earnings->transactions()
            ->where('type', 'credit')
            ->where('description', 'like', 'Bot trading result%')
            ->whereIn('reference_type', [PoolAllocation::class])
            ->latest('id')
            ->limit(20)
            ->get();

        return view('livewire.trade', [
            'pool' => $pool,
            'nav' => $nav,
            'botRunning' => $botRunning,
            'pair' => $this->pair,
            'timeframe' => $this->timeframe,
            'pairs' => $pairs,
            'timeframes' => $timeframes,
            'allocation' => $allocations->first(),
            'positionValue' => $positionValue,
            'units' => $units,
            'principal' => (float) $principal->balance,
            'withdrawable' => (float) $earnings->balance + (float) $referral->balance,
            'earnings' => (float) $earnings->balance,
            'invested' => $invested,
            'withdrawnFromPool' => $withdrawnFromPool,
            'returnsCredited' => $returnsCredited,
            'totalReturn' => $totalReturn,
            'returnPct' => $returnPct,
            'today' => $today,
            'todayPnl' => $todayPnl,
            'sessions' => $sessions,
            'dailyPayouts' => $dailyPayouts,
        ]);
    }
}
