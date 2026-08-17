<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use App\Models\User;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        User::factory()->create(['referral_code' => 'JIV4TEST']);

        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'Password1234')
            ->set('password_confirmation', 'Password1234')
            ->set('ref', 'JIV4TEST');

        $component->call('register');

        $component->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }
}
