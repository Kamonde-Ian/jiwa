<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\KycVerification;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DebugWidgetRender extends TestCase
{
    use RefreshDatabase;

    public function test_dump_pending_reviews_widget(): void
    {
        $user = User::factory()->create(['name' => 'Alice Smith']);
        $user2 = User::factory()->create(['name' => 'Bob Jones']);

        Deposit::create([
            'user_id' => $user->id,
            'network' => 'usdt_trc20',
            'currency' => 'USDT',
            'tx_hash' => 'TX-PENDING-1',
            'amount_usd' => 250.50,
            'status' => Deposit::STATUS_PENDING,
        ]);
        Withdrawal::create([
            'user_id' => $user2->id,
            'wallet_type' => 'principal',
            'amount' => 100,
            'fee' => 1,
            'network' => 'btc',
            'destination_address' => 'bc1qwithdraw',
            'status' => Withdrawal::STATUS_PENDING_REVIEW,
        ]);
        KycVerification::create([
            'user_id' => $user->id,
            'document_type' => 'passport',
            'document_path' => 'passports/test.jpg',
            'status' => KycVerification::STATUS_PENDING,
        ]);

        $component = Livewire::test(\App\Filament\Widgets\PendingReviewsWidget::class);
        $html = $component->html();

        file_put_contents(sys_get_temp_dir() . '/debug_widget.html', $html);

        $this->assertTrue(true);
    }
}
