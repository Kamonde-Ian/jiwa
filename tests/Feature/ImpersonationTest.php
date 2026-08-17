<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class ImpersonationTest extends TestCase
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

    public function test_non_admin_cannot_impersonate(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.impersonate', $target))
            ->assertForbidden();
    }

    public function test_admin_cannot_impersonate_self(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('admin.impersonate', $admin))
            ->assertForbidden();
    }

    public function test_admin_cannot_impersonate_another_admin(): void
    {
        $admin = $this->adminUser();
        $otherAdmin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('admin.impersonate', $otherAdmin))
            ->assertForbidden();
    }

    public function test_admin_can_impersonate_a_user(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.impersonate', $target));

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::id() === $target->id);
        $this->assertEquals($admin->id, session('impersonator_id'));
    }

    public function test_admin_can_leave_impersonation(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.impersonate', $target));
        $this->assertTrue(Auth::id() === $target->id);

        $response = $this->post(route('impersonate.leave'));
        $response->assertRedirect(route('filament.admin.pages.dashboard'));
        $this->assertTrue(Auth::id() === $admin->id);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_logout_while_impersonating_returns_to_admin(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.impersonate', $target));

        $response = $this->post('/logout');
        $response->assertRedirect(route('filament.admin.pages.dashboard'));
        $this->assertTrue(Auth::id() === $admin->id);
    }

    public function test_impersonated_user_sees_preview_banner(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create(['name' => 'Impersonated Jane']);

        $this->actingAs($admin)->get(route('admin.impersonate', $target));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Admin preview')
            ->assertSee('Impersonated Jane');
    }
}
