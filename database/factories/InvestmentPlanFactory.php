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
            'daily_rate' => 0.005000,
            'min_amount' => 50,
            'is_active' => true,
            'is_custom' => false,
        ];
    }
}
