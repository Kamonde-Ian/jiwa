<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\KycVerification;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugDashboardRender extends TestCase
{
    use RefreshDatabase;

    public function test_dump_dashboard_html(): void
    {
        $role = \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
        foreach (\Database\Seeders\RoleSeeder::ADMIN_PERMISSIONS as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
        }
        $role->syncPermissions(\Database\Seeders\RoleSeeder::ADMIN_PERMISSIONS);
        $admin = tap(User::factory()->create(['name' => 'Admin Dump']), fn ($u) => $u->assignRole('admin'));

        Deposit::create([
            'user_id' => $admin->id,
            'network' => 'usdt_trc20',
            'currency' => 'USDT',
            'tx_hash' => 'TX-PENDING-1',
            'amount_usd' => 250.50,
            'status' => Deposit::STATUS_PENDING,
        ]);
        Withdrawal::create([
            'user_id' => $admin->id,
            'wallet_type' => 'principal',
            'amount' => 100,
            'fee' => 1,
            'network' => 'btc',
            'destination_address' => 'bc1qwithdraw',
            'status' => Withdrawal::STATUS_PENDING_REVIEW,
        ]);
        KycVerification::create([
            'user_id' => $admin->id,
            'document_type' => 'passport',
            'document_path' => 'passports/test.jpg',
            'status' => KycVerification::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertOk();

        $html = $response->getContent();
        file_put_contents(sys_get_temp_dir() . '/debug_dashboard.html', $html);

        $this->assertTrue(true);
    }
}
