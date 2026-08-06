<?php

namespace Tests\Unit;

use App\Domain\Deposits\DepositService;
use App\Domain\Wallets\WalletService;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DepositService $service;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DepositService::class);
        $this->walletService = app(WalletService::class);
    }

    public function test_request_creates_pending_deposit(): void
    {
        $user = User::factory()->create();

        $deposit = $this->service->request($user, 'usdt_trc20', 'tx123abc', 500);

        $this->assertDatabaseHas('deposits', [
            'id' => $deposit->id,
            'user_id' => $user->id,
            'network' => 'usdt_trc20',
            'currency' => 'USDT',
            'tx_hash' => 'tx123abc',
            'status' => Deposit::STATUS_PENDING,
        ]);
    }

    public function test_request_rejects_unsupported_network(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->request(User::factory()->create(), 'doge', 'tx', 100);
    }

    public function test_request_rejects_duplicate_tx_hash(): void
    {
        $user = User::factory()->create();
        $this->service->request($user, 'btc', 'dup_hash', 100);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->request(User::factory()->create(), 'btc', 'dup_hash', 200);
    }

    public function test_confirm_credits_principal_wallet_once(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $deposit = $this->service->request($user, 'eth', 'tx_confirm', 250);

        $this->service->confirm($deposit, $admin, 'Verified on explorer');

        $this->assertDatabaseHas('deposits', [
            'id' => $deposit->id,
            'status' => Deposit::STATUS_CONFIRMED,
            'confirmed_by' => $admin->id,
        ]);

        $this->assertEquals(
            250,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL)->balance,
        );

        // Confirming again must not double-credit.
        $this->service->confirm($deposit->fresh(), $admin);

        $this->assertEquals(
            250,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL)->balance,
        );
    }

    public function test_reject_does_not_credit(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $deposit = $this->service->request($user, 'btc', 'tx_reject', 100);

        $this->service->reject($deposit, $admin, 'Tx not found');

        $this->assertDatabaseHas('deposits', [
            'id' => $deposit->id,
            'status' => Deposit::STATUS_REJECTED,
            'admin_note' => 'Tx not found',
        ]);

        $this->assertEquals(
            0,
            (float) $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL)->balance,
        );
    }
}
