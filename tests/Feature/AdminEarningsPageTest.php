<?php

namespace Tests\Feature;

use App\Domain\Deposits\DepositService;
use App\Domain\Wallets\WalletService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEarningsPageTest extends TestCase
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

    public function test_non_admin_cannot_access_admin_earnings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/admin-earnings')
            ->assertForbidden();
    }

    public function test_admin_earnings_page_renders(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get('/admin/admin-earnings')
            ->assertOk()
            ->assertSee('Automated Admin Earnings')
            ->assertSee('Run distribution now')
            ->assertSee('Admin Balances')
            ->assertSee('Recent Distributions');
    }

    public function test_distribute_now_credits_admins(): void
    {
        $admin = $this->adminUser();
        // Two more admins so there are three to split between.
        for ($i = 0; $i < 2; $i++) {
            User::factory()->create()->assignRole('admin');
        }

        $user = User::factory()->create();
        $deposit = app(DepositService::class)->request($user, 'eth', uniqid('tx_', true), 1500);
        app(DepositService::class)->confirm($deposit, $admin);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\AdminEarnings::class)
            ->call('distributeNow')
            ->assertHasNoErrors();

        // 2% of $1500 = $30 split among 3 admins.
        $this->assertSame(3, \App\Models\AdminEarning::where('source_type', \App\Models\Deposit::class)->count());
        $this->assertEqualsWithDelta(
            30.0,
            (float) \App\Models\AdminEarning::where('source_type', \App\Models\Deposit::class)->sum('amount'),
            0.01,
        );

        foreach (app(\App\Domain\AdminEarnings\AdminEarningsService::class)->admins() as $adminUser) {
            $balance = (float) app(WalletService::class)
                ->getOrCreate($adminUser, \App\Models\Wallet::TYPE_ADMIN_EARNINGS)
                ->balance;

            $this->assertEqualsWithDelta(10.0, $balance, 0.01);
        }
    }
}