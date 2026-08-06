<?php

namespace Tests\Feature;

use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_sent(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_session_times_out_after_inactivity(): void
    {
        config(['jiwa.session_timeout_minutes' => 1]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['last_activity' => now()->subMinutes(10)])
            ->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_active_session_does_not_time_out(): void
    {
        config(['jiwa.session_timeout_minutes' => 60]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['last_activity' => now()])
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_strong_password_rule_rejects_weak_passwords(): void
    {
        $rule = new StrongPassword();
        $fail = function () {
            $this->fails = true;
        };

        $rule->validate('password', 'short', $fail);
        $this->assertTrue($this->fails ?? false);

        unset($this->fails);
        $rule->validate('password', 'abcdefghij', $fail); // letters only, no number
        $this->assertTrue($this->fails ?? false);

        unset($this->fails);
        $rule->validate('password', 'abcdef1234', $fail); // good
        $this->assertFalse($this->fails ?? false);
    }

    public function test_registration_rejects_weak_password(): void
    {
        \Livewire\Volt\Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
