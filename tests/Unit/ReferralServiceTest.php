<?php

namespace Tests\Unit;

use App\Domain\Investments\InvestmentService;
use App\Domain\Referrals\ReferralService;
use App\Domain\Wallets\WalletService;
use App\Models\InvestmentPlan;
use App\Models\ReferralEarning;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReferralServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReferralService $service;

    protected InvestmentService $investmentService;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReferralService::class);
        $this->investmentService = app(InvestmentService::class);
        $this->walletService = app(WalletService::class);
    }

    protected function fundedUser(float $amount = 1000): User
    {
        $user = User::factory()->create();
        $this->walletService->credit(
            $this->walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL),
            $amount,
            'Deposit',
        );

        return $user;
    }

    public function test_commission_credited_to_qualified_referrer(): void
    {
        $referrer = $this->fundedUser();
        $this->investmentService->create($referrer, InvestmentPlan::factory()->create(), 200);

        $investor = $this->fundedUser();
        $investor->update(['referred_by' => $referrer->id]);

        $investment = $this->investmentService->create($investor, InvestmentPlan::factory()->create(), 1000);

        // The InvestmentCreated event listener credits the commission.
        $earning = ReferralEarning::where('investment_id', $investment->id)->first();

        $this->assertNotNull($earning);
        $this->assertEquals(30.0, (float) $earning->amount); // 3% of 1000
        $this->assertDatabaseHas('referral_earnings', [
            'referrer_id' => $referrer->id,
            'investment_id' => $investment->id,
        ]);

        $this->assertEquals(
            30.0,
            (float) $this->walletService->getOrCreate($referrer, Wallet::TYPE_REFERRAL)->balance,
        );
    }

    public function test_commission_is_idempotent(): void
    {
        $referrer = $this->fundedUser();
        $this->investmentService->create($referrer, InvestmentPlan::factory()->create(), 200);

        $investor = $this->fundedUser();
        $investor->update(['referred_by' => $referrer->id]);

        $investment = $this->investmentService->create($investor, InvestmentPlan::factory()->create(), 1000);

        $this->service->creditCommission($investment);
        $this->assertNull($this->service->creditCommission($investment));

        $this->assertEquals(
            30.0,
            (float) $this->walletService->getOrCreate($referrer, Wallet::TYPE_REFERRAL)->balance,
        );
        $this->assertEquals(1, ReferralEarning::count());
    }

    public function test_no_commission_when_investor_has_no_referrer(): void
    {
        $investor = $this->fundedUser();
        $investment = $this->investmentService->create($investor, InvestmentPlan::factory()->create(), 1000);

        $this->assertNull($this->service->creditCommission($investment));
        $this->assertEquals(0, ReferralEarning::count());
    }

    public function test_no_commission_when_referrer_unqualified(): void
    {
        $referrer = User::factory()->create(); // no active investment
        $investor = $this->fundedUser();
        $investor->update(['referred_by' => $referrer->id]);

        $investment = $this->investmentService->create($investor, InvestmentPlan::factory()->create(), 1000);

        $this->assertNull($this->service->creditCommission($investment));
        $this->assertEquals(0, ReferralEarning::count());
    }

    public function test_commission_uses_configured_rate(): void
    {
        config(['jiwa.referral_commission_rate' => 0.05]);

        $referrer = $this->fundedUser();
        $this->investmentService->create($referrer, InvestmentPlan::factory()->create(), 200);

        $investor = $this->fundedUser();
        $investor->update(['referred_by' => $referrer->id]);

        $investment = $this->investmentService->create($investor, InvestmentPlan::factory()->create(), 1000);

        $earning = ReferralEarning::where('investment_id', $investment->id)->first();

        $this->assertNotNull($earning);
        $this->assertEquals(50.0, (float) $earning->amount);
    }

    public function test_investment_created_event_triggers_commission(): void
    {
        Event::fake([\App\Events\InvestmentCreated::class]);

        $referrer = $this->fundedUser();
        $this->investmentService->create($referrer, InvestmentPlan::factory()->create(), 200);

        $investor = $this->fundedUser();
        $investor->update(['referred_by' => $referrer->id]);

        $investment = $this->investmentService->create($investor, InvestmentPlan::factory()->create(), 500);

        Event::assertDispatched(\App\Events\InvestmentCreated::class, fn ($e) => $e->investment->id === $investment->id);
    }

    public function test_referral_link_contains_code(): void
    {
        $referrer = User::factory()->create(['referral_code' => 'ABC12345']);

        $this->assertStringContainsString('ref=ABC12345', $this->service->referralLink($referrer));
    }

    public function test_qualification_status(): void
    {
        $unqualified = User::factory()->create();
        $this->assertFalse($this->service->isQualified($unqualified));

        $qualified = $this->fundedUser();
        $this->investmentService->create($qualified, InvestmentPlan::factory()->create(), 200);
        $this->assertTrue($this->service->isQualified($qualified));

        // Qualification minimum is respected.
        $barelyQualified = $this->fundedUser();
        $this->investmentService->create($barelyQualified, InvestmentPlan::factory()->create(), 50);
        $this->assertFalse($this->service->isQualified($barelyQualified));
    }
}
