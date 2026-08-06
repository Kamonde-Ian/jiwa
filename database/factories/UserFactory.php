<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone' => fake()->e164PhoneNumber(),
            'country' => fake()->country(),
            'referral_code' => User::generateReferralCode(),
            'kyc_status' => User::KYC_UNVERIFIED,
            'two_factor_enabled' => false,
        ];
    }

    public function kycVerified(): static
    {
        return $this->state(fn (array $attributes) => [
            'kyc_status' => User::KYC_VERIFIED,
        ]);
    }

    public function withTwoFactor(string $secret = null): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_enabled' => true,
            'google2fa_secret' => $secret ?? app(\PragmaRX\Google2FALaravel\Google2FA::class)->generateSecretKey(),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
