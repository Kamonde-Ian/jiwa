<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TwoFactorAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_2fa_logs_in_directly(): void
    {
        $user = User::factory()->create();

        Livewire::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_with_2fa_is_redirected_to_challenge(): void
    {
        $twoFactor = app(TwoFactorAuth::class);
        $secret = $twoFactor->generateSecret();
        $user = User::factory()->withTwoFactor($secret)->create();

        Livewire::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect('/two-factor-challenge');

        $this->assertGuest();
        $this->assertEquals($user->id, session('two_factor_pending_user'));
    }

    public function test_2fa_challenge_with_valid_code_authenticates(): void
    {
        $twoFactor = app(TwoFactorAuth::class);
        $secret = $twoFactor->generateSecret();
        $user = User::factory()->withTwoFactor($secret)->create();

        Session::put('two_factor_pending_user', $user->id);

        $code = app(\PragmaRX\Google2FALaravel\Google2FA::class)->getCurrentOtp($secret);

        Livewire::test('pages.auth.two-factor-challenge')
            ->set('code', $code)
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_2fa_challenge_with_invalid_code_rejected(): void
    {
        $twoFactor = app(TwoFactorAuth::class);
        $secret = $twoFactor->generateSecret();
        $user = User::factory()->withTwoFactor($secret)->create();

        Session::put('two_factor_pending_user', $user->id);

        Livewire::test('pages.auth.two-factor-challenge')
            ->set('code', '000000')
            ->call('confirm')
            ->assertHasErrors('code');

        $this->assertGuest();
    }

    public function test_withdrawal_requires_2fa_enabled(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => false]);

        $this->assertFalse(app(TwoFactorAuth::class)->canWithdraw($user));

        $user2 = User::factory()->withTwoFactor()->create();

        $this->assertTrue(app(TwoFactorAuth::class)->canWithdraw($user2));
    }

    public function test_security_page_enables_2fa_with_valid_code(): void
    {
        $user = User::factory()->create();
        $twoFactor = app(TwoFactorAuth::class);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Security::class)
            ->call('beginSetup')
            ->assertSet('showSetup', true);

        $secret = Livewire::actingAs($user)
            ->test(\App\Livewire\Security::class)
            ->call('beginSetup')
            ->get('pendingSecret');

        $code = app(\PragmaRX\Google2FALaravel\Google2FA::class)->getCurrentOtp($secret);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Security::class)
            ->set('pendingSecret', $secret)
            ->set('verificationCode', $code)
            ->call('confirmSetup')
            ->assertHasNoErrors();

        $this->assertTrue($user->fresh()->two_factor_enabled);
    }
}
