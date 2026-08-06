<?php

namespace Database\Seeders;

use App\Models\InvestmentPlan;
use Illuminate\Database\Seeder;

class InvestmentPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Starter', 'duration_days' => 30, 'daily_rate' => 0.005, 'min_amount' => 50],
            ['name' => 'Growth', 'duration_days' => 90, 'daily_rate' => 0.005, 'min_amount' => 200],
            ['name' => 'Advantage', 'duration_days' => 180, 'daily_rate' => 0.005, 'min_amount' => 500],
            ['name' => 'Pro', 'duration_days' => 365, 'daily_rate' => 0.005, 'min_amount' => 1000],
            ['name' => 'Elite', 'duration_days' => 730, 'daily_rate' => 0.005, 'min_amount' => 5000],
        ];

        foreach ($plans as $plan) {
            InvestmentPlan::updateOrCreate(
                ['name' => $plan['name']],
                [...$plan, 'is_active' => true, 'is_custom' => false],
            );
        }
    }
}
