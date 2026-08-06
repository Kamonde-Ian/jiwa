<?php

namespace Tests\Feature;

use App\Domain\Deposits\DepositService;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DepositsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/deposits')
            ->assertOk()
            ->assertSee('Request a Deposit');
    }

    public function test_user_can_submit_deposit_request(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Deposits::class)
            ->set('network', 'btc')
            ->set('tx_hash', 'abc123tx')
            ->set('amount_usd', 250)
            ->call('request')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('deposits', [
            'user_id' => $user->id,
            'network' => 'btc',
            'tx_hash' => 'abc123tx',
            'status' => Deposit::STATUS_PENDING,
        ]);
    }

    public function test_deposit_page_shows_submitted_deposits(): void
    {
        $user = User::factory()->create();
        app(DepositService::class)->request($user, 'usdt_trc20', 'submitted_tx', 100);

        $this->actingAs($user)
            ->get('/deposits')
            ->assertOk()
            ->assertSee('submitted_tx')
            ->assertSee('Pending');
    }

    public function test_deposit_page_requires_auth(): void
    {
        $this->get('/deposits')->assertRedirect('/login');
    }
}
