<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_without_referral_code_assigns_unique_code(): void
    {
        Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'Password1234')
            ->set('password_confirmation', 'Password1234')
            ->call('register');

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($user->referral_code);
        $this->assertNull($user->referred_by);
        $this->assertEquals(8, strlen($user->referral_code));
    }

    public function test_registration_with_valid_referral_code_links_referrer(): void
    {
        $referrer = User::factory()->create(['referral_code' => 'JIV4TEST']);

        Volt::test('pages.auth.register')
            ->set('name', 'Referred User')
            ->set('email', 'ref@example.com')
            ->set('password', 'Password1234')
            ->set('password_confirmation', 'Password1234')
            ->set('ref', 'JIV4TEST')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'ref@example.com',
            'referred_by' => $referrer->id,
        ]);
    }

    public function test_registration_with_invalid_referral_code_rejected(): void
    {
        Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'Password1234')
            ->set('password_confirmation', 'Password1234')
            ->set('ref', 'DOESNOTEXIST')
            ->call('register')
            ->assertHasErrors('ref');

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_followed_by_dashboard_access(): void
    {
        Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'Password1234')
            ->set('password_confirmation', 'Password1234')
            ->call('register')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->get('/dashboard')->assertOk();
    }

    public function test_registration_wizard_renders_first_step(): void
    {
        Volt::test('pages.auth.register')
            ->assertSee('Account')
            ->assertSee('Security')
            ->assertSee('Referral')
            ->assertSee('Continue')
            ->assertSee('Name')
            ->assertSee('Email')
            ->assertDontSee('Referral Code (optional)');
    }

    public function test_registration_wizard_progresses_step_by_step(): void
    {
        $component = Volt::test('pages.auth.register');

        // Cannot advance past step 1 without name/email
        $component->call('nextStep')->assertHasErrors(['name', 'email']);

        // Fill step 1 and advance
        $component->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->assertHasNoErrors();

        // Weak password blocks advancing past step 2
        $component->set('password', 'weak')
            ->set('password_confirmation', 'weak')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->assertHasErrors('password');

        // Strong password advances to step 3
        $component->set('password', 'Password1234')
            ->set('password_confirmation', 'Password1234')
            ->call('nextStep')
            ->assertSet('step', 3)
            ->assertHasNoErrors();

        // Back returns to step 2 and clears errors
        $component->call('previousStep')->assertSet('step', 2)->assertHasNoErrors();

        // Forward again and complete registration
        $component->call('nextStep')
            ->assertSet('step', 3)
            ->call('register')
            ->assertHasNoErrors();
    }
}


