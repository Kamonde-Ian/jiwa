<?php

namespace Tests\Unit;

use App\Domain\Wallets\Exceptions\InsufficientBalanceException;
use App\Domain\Wallets\Exceptions\PrincipalWalletLockedException;
use App\Domain\Wallets\WalletService;
use App\Models\Investment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WalletService::class);
    }

    public function test_credit_updates_balance_and_records_ledger(): void
    {
        $user = User::factory()->create();
        $wallet = $this->service->getOrCreate($user, Wallet::TYPE_EARNINGS);

        $tx = $this->service->credit($wallet, 150.5, 'Test credit');

        $this->assertEquals(150.5, $wallet->fresh()->balance);
        $this->assertEquals(WalletTransaction::TYPE_CREDIT, $tx->type);
        $this->assertEquals(150.5, $tx->balance_after);
    }

    public function test_debit_updates_balance_and_records_ledger(): void
    {
        $user = User::factory()->create();
        $wallet = $this->service->getOrCreate($user, Wallet::TYPE_EARNINGS);
        $this->service->credit($wallet, 100);

        $tx = $this->service->debit($wallet, 40, 'Test debit');

        $this->assertEquals(60, $wallet->fresh()->balance);
        $this->assertEquals(WalletTransaction::TYPE_DEBIT, $tx->type);
        $this->assertEquals(60, $tx->balance_after);
    }

    public function test_debit_with_insufficient_balance_throws(): void
    {
        $this->expectException(InsufficientBalanceException::class);

        $user = User::factory()->create();
        $wallet = $this->service->getOrCreate($user, Wallet::TYPE_EARNINGS);

        $this->service->debit($wallet, 10);
    }

    public function test_credit_with_negative_amount_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $wallet = $this->service->getOrCreate($user, Wallet::TYPE_EARNINGS);

        $this->service->credit($wallet, -5);
    }

    public function test_principal_wallet_debit_rejected_while_investment_active(): void
    {
        $this->expectException(PrincipalWalletLockedException::class);

        $user = User::factory()->create();
        $wallet = $this->service->getOrCreate($user, Wallet::TYPE_PRINCIPAL);
        $this->service->credit($wallet, 500);

        Investment::factory()->for($user)->create(['status' => Investment::STATUS_ACTIVE]);

        $this->service->debit($wallet, 100);
    }

    public function test_principal_wallet_debit_allowed_without_active_investments(): void
    {
        $user = User::factory()->create();
        $wallet = $this->service->getOrCreate($user, Wallet::TYPE_PRINCIPAL);
        $this->service->credit($wallet, 500);

        $this->service->debit($wallet, 100);

        $this->assertEquals(400, $wallet->fresh()->balance);
    }

    public function test_principal_wallet_debit_allowed_internally_while_investment_active(): void
    {
        $user = User::factory()->create();
        $wallet = $this->service->getOrCreate($user, Wallet::TYPE_PRINCIPAL);
        $this->service->credit($wallet, 500);

        Investment::factory()->for($user)->create(['status' => Investment::STATUS_ACTIVE]);

        // Internal allocations (e.g. creating another investment) opt in explicitly.
        $this->service->debit($wallet, 100, allowLocked: true);

        $this->assertEquals(400, $wallet->fresh()->balance);
    }

    public function test_transfer_moves_funds_between_wallets(): void
    {
        $user = User::factory()->create();
        $principal = $this->service->getOrCreate($user, Wallet::TYPE_PRINCIPAL);
        $earnings = $this->service->getOrCreate($user, Wallet::TYPE_EARNINGS);
        $this->service->credit($principal, 1000);

        [$debit, $credit] = $this->service->transfer($principal, $earnings, 300, 'Principal release');

        $this->assertEquals(700, $principal->fresh()->balance);
        $this->assertEquals(300, $earnings->fresh()->balance);
        $this->assertEquals(700, $debit->balance_after);
        $this->assertEquals(300, $credit->balance_after);
    }

    public function test_transactions_carry_reference(): void
    {
        $user = User::factory()->create();
        $investment = Investment::factory()->for($user)->create();

        $wallet = $this->service->getOrCreate($user, Wallet::TYPE_EARNINGS);
        $this->service->credit($wallet, 10, 'Interest', $investment);

        $tx = $wallet->fresh()->transactions()->latest('id')->first();

        $this->assertEquals(Investment::class, $tx->reference_type);
        $this->assertEquals($investment->id, $tx->reference_id);
    }
}
