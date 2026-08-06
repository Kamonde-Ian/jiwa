<?php

namespace Tests\Feature;

use App\Domain\Wallets\WalletService;
use App\Models\Deposit;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Support\TwoFactorAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        $role = \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
        foreach (\Database\Seeders\RoleSeeder::ADMIN_PERMISSIONS as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
        }
        $role->syncPermissions(\Database\Seeders\RoleSeeder::ADMIN_PERMISSIONS);

        return tap(User::factory()->create(), fn (User $user) => $user->assignRole('admin'));
    }

    public function test_admin_login_page_renders(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Sign in')
            ->assertSee('JIWA Admin')
            ->assertSee('Demo admin account')
            ->assertSee('admin@jiwa.test')
            ->assertSee('Autofill');
    }

    public function test_admin_login_demo_autofill_fills_credentials(): void
    {
        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->call('fillDemo')
            ->assertSet('data.email', 'admin@jiwa.test')
            ->assertSet('data.password', 'password')
            ->assertSet('data.remember', true);
    }

    public function test_non_admin_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_password_reset_pages_render_with_shared_design(): void
    {
        $this->get('/admin/password-reset/request')
            ->assertOk()
            ->assertSee('Reset your password')
            ->assertSee('JIWA Admin')
            ->assertSee('Back to login');

        $user = $this->adminUser();

        $signedUrl = \Illuminate\Support\Facades\URL::signedRoute(
            'filament.admin.auth.password-reset.reset',
            ['email' => $user->email, 'token' => 'testtoken123'],
        );

        $this->get($signedUrl)
            ->assertOk()
            ->assertSee('Choose a new password')
            ->assertSee('JIWA Admin');
    }

    public function test_investment_resources_render(): void
    {
        $admin = $this->adminUser();

        $plan = InvestmentPlan::factory()->create();
        $user = User::factory()->create();

        $walletService = app(WalletService::class);
        $walletService->credit(
            $walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL),
            1000,
            'Deposit',
        );

        $investment = app(\App\Domain\Investments\InvestmentService::class)
            ->create($user, $plan, 100);

        $this->actingAs($admin)
            ->get('/admin/investment-plans')
            ->assertOk()
            ->assertSee($plan->name);

        $this->actingAs($admin)
            ->get("/admin/investment-plans/{$plan->id}/edit")
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/investments')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/admin/investments/{$investment->id}")
            ->assertOk()
            ->assertSee($user->name);
    }

    public function test_admin_dashboard_renders(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Welcome back');

        Livewire::test(\App\Filament\Widgets\StatsOverviewWidget::class)
            ->assertSee('Total Users')
            ->assertSee('Active Investments')
            ->assertSee('Pending Deposits')
            ->assertSee('Pending Withdrawals');
    }

    public function test_settings_page_renders(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Investments')
            ->assertSee('Crypto Deposit Addresses');
    }

    public function test_settings_save_persists_values(): void
    {
        $admin = $this->adminUser();

        Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\Settings::class)
            ->fillForm([
                'min_investment' => 25,
                'default_daily_rate' => 0.005,
                'interest_credit_hours' => 24,
                'matured_principal_destination' => 'principal',
                'referral_commission_rate' => 0.04,
                'referral_qualification_minimum' => 150,
                'min_withdrawal' => 15,
                'withdrawal_fee' => 1,
                'withdrawal_auto_approve_threshold' => 300,
                'withdrawal_manual_threshold' => 5000,
                'btc_address' => 'bc1qtest',
                'usdt_bep20_address' => '0xJIWA_bep20_test',
                'bnb_address' => 'bnb1test',
            ])
            ->call('save');

        $this->assertEquals(25, \App\Support\PlatformSettings::config('jiwa.min_investment'));
        $this->assertEquals(0.04, \App\Support\PlatformSettings::config('jiwa.referral_commission_rate'));
        $this->assertEquals('bc1qtest', \App\Support\PlatformSettings::config('jiwa.networks.btc.deposit_address'));
        $this->assertEquals('0xJIWA_bep20_test', \App\Support\PlatformSettings::config('jiwa.networks.usdt_bep20.deposit_address'));
        $this->assertEquals('bnb1test', \App\Support\PlatformSettings::config('jiwa.networks.bnb.deposit_address'));
    }

    public function test_role_resource_renders(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get('/admin/roles')
            ->assertOk()
            ->assertSee('admin');
    }

    public function test_funds_resources_render(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();

        app(\App\Domain\Deposits\DepositService::class)->request($user, 'usdt_trc20', 'smoke_tx', 100);

        $twoFactor = app(TwoFactorAuth::class);
        $secret = $twoFactor->generateSecret();
        $withdrawer = User::factory()->kycVerified()->withTwoFactor($secret)->create();
        app(WalletService::class)->credit(
            app(WalletService::class)->getOrCreate($withdrawer, Wallet::TYPE_EARNINGS),
            500,
            'Interest',
        );
        $otp = app(\PragmaRX\Google2FALaravel\Google2FA::class)->getCurrentOtp($secret);
        app(\App\Domain\Withdrawals\WithdrawalService::class)->request(
            $withdrawer,
            Wallet::TYPE_EARNINGS,
            100,
            'btc',
            'bc1qsmoke',
            $otp,
        );

        $this->actingAs($admin)
            ->get('/admin/deposits')
            ->assertOk()
            ->assertSee('smoke_tx');

        $this->actingAs($admin)
            ->get('/admin/withdrawals')
            ->assertOk()
            ->assertSee('bc1qsmoke');
    }
}
