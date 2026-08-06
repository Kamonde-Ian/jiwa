<?php

namespace Database\Factories;

use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Investment>
 */
class InvestmentFactory extends Factory
{
    protected $model = Investment::class;

    public function definition(): array
    {
        $plan = InvestmentPlan::factory()->create();
        $startsAt = now();
        $maturesAt = $startsAt->copy()->addDays($plan->duration_days);

        return [
            'user_id' => User::factory(),
            'plan_id' => $plan->id,
            'principal_amount' => 1000,
            'daily_rate_snapshot' => $plan->daily_rate,
            'status' => Investment::STATUS_ACTIVE,
            'starts_at' => $startsAt,
            'matures_at' => $maturesAt,
            'last_interest_credited_at' => $startsAt,
        ];
    }

    public function matured(): static
    {
        return $this->state(fn () => [
            'status' => Investment::STATUS_MATURED,
            'matures_at' => now()->subDay(),
        ]);
    }
}
