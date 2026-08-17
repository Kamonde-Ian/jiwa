<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminPasswordManagementTest extends TestCase
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

    public function test_registration_stores_encrypted_plaintext_copy(): void
    {
        $referrer = User::factory()->create(['referral_code' => 'JIV4TEST']);

        Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'Password1234')
            ->set('password_confirmation', 'Password1234')
            ->set('ref', 'JIV4TEST')
            ->call('register');

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('Password1234', Crypt::decryptString($user->getRawOriginal('password_plain')));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Password1234', $user->password));
    }

    public function test_admin_change_password_action_sets_plaintext_copy(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();

        $user->setPassword('OldPass1234');
        $user->save();

        Livewire::actingAs($admin)
            ->test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->callAction('changePassword', [
                'password' => 'NewPass5678',
                'password_confirmation' => 'NewPass5678',
            ]);

        $user->refresh();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPass5678', $user->password));
        $this->assertEquals('NewPass5678', Crypt::decryptString($user->getRawOriginal('password_plain')));
    }

    public function test_admin_change_password_action_requires_confirmation(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->callAction('changePassword', [
                'password' => 'NewPass5678',
                'password_confirmation' => 'Different1234',
            ])->assertHasActionErrors();

        $user->refresh();
        $this->assertNull($user->getRawOriginal('password_plain'));
    }

    public function test_admin_edit_form_password_stores_plaintext_copy(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'EditedPass9012',
                'phone' => $user->phone,
                'country' => $user->country,
                'referral_code' => $user->referral_code,
                'kyc_status' => $user->kyc_status,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('EditedPass9012', $user->password));
        $this->assertEquals('EditedPass9012', Crypt::decryptString($user->getRawOriginal('password_plain')));
    }

    public function test_view_user_reveals_current_password(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();

        $user->setPassword('ShownPass1234');
        $user->save();

        $this->actingAs($admin)
            ->get("/admin/users/{$user->id}")
            ->assertOk()
            ->assertSee('Current password')
            ->assertSee('ShownPass1234');
    }
}