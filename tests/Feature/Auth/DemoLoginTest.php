<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_user_can_log_in_after_seeding(): void
    {
        $this->seed();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', 'user@jiwa.test')
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_demo_admin_can_log_in_after_seeding(): void
    {
        $this->seed();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', 'admin@jiwa.test')
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_fill_demo_sets_credentials_that_match_the_seeder(): void
    {
        $component = Volt::test('pages.auth.login')->call('fillDemo');

        $component->assertSet('form.email', 'user@jiwa.test')
            ->assertSet('form.password', 'password')
            ->assertSet('form.remember', true);
    }

    public function test_seeder_re_runs_do_not_break_demo_login(): void
    {
        $this->seed();
        $this->seed();
        $this->seed();

        $user = \App\Models\User::where('email', 'user@jiwa.test')->first();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $user->password));
        $this->assertFalse((bool) $user->two_factor_enabled);
    }
}
