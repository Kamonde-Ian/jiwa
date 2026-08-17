<?php

namespace App\Domain\AdminEarnings;

use App\Domain\Wallets\WalletService;
use App\Models\AdminEarning;
use App\Models\Deposit;
use App\Models\Investment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Automates the three platform admins' earnings.
 *
 * A configurable commission rate is applied to the USD value of completed
 * platform transactions (confirmed deposits, completed withdrawals and active
 * investments) and the resulting amount is split equally among the admins who
 * hold the `admin` role. Each admin is credited through WalletService into
 * their dedicated `admin_earnings` wallet.
 *
 * Distribution is idempotent: one AdminEarning row per admin per source
 * transaction, guarded by a unique key, so re-running never double-credits.
 */
class AdminEarningsService
{
    /**
     * Sources whose completion triggers an admin earning event.
     */
    public const SOURCES = [
        Deposit::class,
        Withdrawal::class,
        Investment::class,
    ];

    public function __construct(protected WalletService $walletService)
    {
    }

    /**
     * @return Collection<int, User>
     */
    public function admins(): Collection
    {
        return User::query()
            ->role('admin')
            ->orderBy('id')
            ->get();
    }

    public function enabled(): bool
    {
        return (bool) \App\Support\PlatformSettings::config('jiwa.admin_earnings_enabled');
    }

    public function rate(): float
    {
        return (float) \App\Support\PlatformSettings::config('jiwa.admin_earnings_rate');
    }

    /**
     * The USD value a given source transaction is commissioned on.
     */
    protected function baseValue(Model $source): float
    {
        return match (true) {
            $source instanceof Deposit => (float) $source->amount_usd,
            $source instanceof Withdrawal => (float) $source->amount + (float) $source->fee,
            $source instanceof Investment => (float) $source->principal_amount,
            default => 0.0,
        };
    }

    protected function label(Model $source): string
    {
        return match (true) {
            $source instanceof Deposit => 'deposit',
            $source instanceof Withdrawal => 'withdrawal',
            $source instanceof Investment => 'investment',
            default => 'transaction',
        };
    }

    /**
     * Scan the ledger for completed transactions that have not yet generated
     * admin earnings and distribute them.
     *
     * @return array{admins: int, deposits: int, withdrawals: int, investments: int, total: float}
     */
    public function distributePending(): array
    {
        $result = [
            'admins' => $this->admins()->count(),
            'deposits' => 0,
            'withdrawals' => 0,
            'investments' => 0,
            'total' => 0.0,
        ];

        if (! $this->enabled() || $result['admins'] === 0) {
            return $result;
        }

        $deposits = Deposit::query()
            ->where('status', Deposit::STATUS_CONFIRMED)
            ->whereDoesntHave('adminEarnings')
            ->get();

        foreach ($deposits as $deposit) {
            $result['total'] += $this->distributeFor($deposit)['total'];
            $result['deposits']++;
        }

        $withdrawals = Withdrawal::query()
            ->where('status', Withdrawal::STATUS_COMPLETED)
            ->whereDoesntHave('adminEarnings')
            ->get();

        foreach ($withdrawals as $withdrawal) {
            $result['total'] += $this->distributeFor($withdrawal)['total'];
            $result['withdrawals']++;
        }

        $investments = Investment::query()
            ->where('status', Investment::STATUS_ACTIVE)
            ->whereDoesntHave('adminEarnings')
            ->get();

        foreach ($investments as $investment) {
            $result['total'] += $this->distributeFor($investment)['total'];
            $result['investments']++;
        }

        return $result;
    }

    /**
     * Distribute admin earnings for a single source transaction. Idempotent:
     * a source that already has earnings on record is skipped.
     *
     * @return array{credited: bool, total: float}
     */
    public function distributeFor(Model $source): array
    {
        if (! $this->enabled()) {
            return ['credited' => false, 'total' => 0.0];
        }

        $admins = $this->admins();
        $count = $admins->count();

        if ($count === 0) {
            return ['credited' => false, 'total' => 0.0];
        }

        if (AdminEarning::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->id)
            ->exists()) {
            return ['credited' => false, 'total' => 0.0];
        }

        $rate = $this->rate();
        $total = round($this->baseValue($source) * $rate, 2);

        if ($total <= 0) {
            return ['credited' => false, 'total' => 0.0];
        }

        $totalCents = (int) round($total * 100);
        $shareCents = intdiv($totalCents, $count);
        $remainderCents = $totalCents - $shareCents * $count;

        foreach ($admins as $i => $admin) {
            $cents = $shareCents + ($i < $remainderCents ? 1 : 0);
            $share = $cents / 100;

            if ($share <= 0) {
                continue;
            }

            DB::transaction(function () use ($source, $admin, $share, $rate) {
                $wallet = $this->walletService->getOrCreate($admin, Wallet::TYPE_ADMIN_EARNINGS);

                $this->walletService->credit(
                    $wallet,
                    $share,
                    "Admin earnings — {$this->label($source)} commission",
                    $source,
                );

                AdminEarning::create([
                    'admin_id' => $admin->id,
                    'source_type' => $source::class,
                    'source_id' => $source->id,
                    'amount' => $share,
                    'rate' => $rate,
                ]);

                activity('admin_earnings')
                    ->performedOn($source)
                    ->causedBy($admin)
                    ->withProperties([
                        'action' => 'admin_earnings_credited',
                        'amount' => $share,
                        'rate' => $rate,
                        'source_type' => $source::class,
                        'source_id' => $source->id,
                    ])
                    ->log("Admin earnings of \${$share} credited");
            });
        }

        return ['credited' => true, 'total' => $total];
    }
}