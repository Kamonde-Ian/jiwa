<?php

namespace Database\Seeders;

use App\Domain\Wallets\WalletService;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            InvestmentPlanSeeder::class,
        ]);

        $this->seedPlatformAddresses();

        $admin = User::updateOrCreate(
            ['email' => 'admin@jiwa.test'],
            [
                'name' => 'Demo Admin',
                'phone' => '+1 555 010 0100',
                'country' => 'United States',
                'password' => 'password',
                'email_verified_at' => now(),
                'kyc_status' => User::KYC_VERIFIED,
                'two_factor_enabled' => false,
            ],
        );
        $admin->assignRole('admin');

        $user = User::updateOrCreate(
            ['email' => 'user@jiwa.test'],
            [
                'name' => 'Demo User',
                'phone' => '+1 555 010 0142',
                'country' => 'United States',
                'password' => 'password',
                'email_verified_at' => now(),
                'referral_code' => User::generateReferralCode(),
                'kyc_status' => User::KYC_UNVERIFIED,
                'two_factor_enabled' => false,
            ],
        );
        $user->assignRole('user');

        // Give the demo user a funded principal wallet so the invest flow can be
        // exercised immediately.
        if ((float) $user->wallets()->where('type', Wallet::TYPE_PRINCIPAL)->first()?->balance === 0.0) {
            $walletService = app(WalletService::class);
            $walletService->credit(
                $walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL),
                5000,
                'Demo funding',
            );
        }

        $this->seedDemoActivity($user);
    }

    /**
     * Give the demo user an active investment with a short history of credited
     * daily interest, so the dashboard charts (Growth Overview, Portfolio,
     * Statements cash-flow) have real data to render.
     */
    protected function seedDemoActivity(User $user): void
    {
        if ($user->investments()->exists()) {
            return;
        }

        $plan = InvestmentPlan::where('is_active', true)->orderBy('min_amount')->first();

        if (! $plan) {
            return;
        }

        $walletService = app(WalletService::class);
        $principal = 2000.0;
        $startsAt = now()->subDays(12)->startOfDay();
        $days = 12;

        $investment = Investment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'principal_amount' => $principal,
            'daily_rate_snapshot' => $plan->daily_rate,
            'status' => Investment::STATUS_ACTIVE,
            'starts_at' => $startsAt,
            'matures_at' => $startsAt->copy()->addDays($plan->duration_days),
            'last_interest_credited_at' => now(),
        ]);
        $investment->forceFill(['created_at' => $startsAt, 'updated_at' => $startsAt])->save();

        $allocation = $walletService->debit(
            $user,
            $principal,
            'Funds allocated to investment',
            null,
            allowLocked: true,
        );
        $allocation->forceFill(['created_at' => $startsAt])->save();

        // Backdate the demo funding deposit to the day before the investment so
        // the ledger timeline reads coherently.
        $user->wallets()->where('type', Wallet::TYPE_PRINCIPAL)->first()?->transactions()
            ->where('description', 'Demo funding')
            ->first()?->forceFill(['created_at' => $startsAt->copy()->subDay()])->save();

        $earnings = $walletService->getOrCreate($user, Wallet::TYPE_EARNINGS);
        $dailyInterest = round($principal * (float) $plan->daily_rate, 8);

        for ($day = 1; $day <= $days; $day++) {
            $creditedOn = $startsAt->copy()->addDays($day);

            $tx = $walletService->credit(
                $earnings,
                $dailyInterest,
                "Daily interest — {$plan->name}",
                $investment,
            );
            $tx->forceFill(['created_at' => $creditedOn])->save();
        }
    }

    /**
     * Sample receiving addresses for the demo environment. Replace these via
     * the admin Settings page in production.
     */
    protected function seedPlatformAddresses(): void
    {
        \App\Support\PlatformSettings::set('networks.btc.deposit_address', 'bc1qjiva_demo_address_0001');
        \App\Support\PlatformSettings::set('networks.eth.deposit_address', '0xJIWA_demo_eth_address_0001');
        \App\Support\PlatformSettings::set('networks.usdt_trc20.deposit_address', 'TJIWA_demo_trc20_address_0001');
        \App\Support\PlatformSettings::set('networks.usdt_erc20.deposit_address', '0xJIWA_demo_erc20_address_0001');
        \App\Support\PlatformSettings::set('networks.usdt_bep20.deposit_address', '0xJIWA_demo_bep20_address_0001');
        \App\Support\PlatformSettings::set('networks.bnb.deposit_address', 'bnb1qjiva_demo_address_0001');
    }
}

