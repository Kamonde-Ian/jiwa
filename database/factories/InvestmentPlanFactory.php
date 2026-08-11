<?php

namespace Database\Factories;

use App\Models\InvestmentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvestmentPlan>
 */
class InvestmentPlanFactory extends Factory
{
    protected $model = InvestmentPlan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->numerify('Plan ###').' days',
            'duration_days' => fake()->randomElement([30, 90, 180, 365, 730]),
            'daily_rate' => 0.001000,
            'min_amount' => 100,
            'max_amount' => null,
            'description' => fake()->randomElement(['Entry', 'Most Popular', 'Better Value', 'Premium', 'VIP']),
            'is_active' => true,
            'is_custom' => false,
        ];
    }
}
