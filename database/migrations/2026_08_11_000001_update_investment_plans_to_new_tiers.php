<?php

use App\Models\InvestmentPlan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Sync the standard plan tiers to the current offering. This is a data
     * migration so the updated rates/limits reach existing databases on
     * deploy (the seeder only runs on a fresh DB).
     */
    public function up(): void
    {
        $plans = [
            ['name' => 'Starter', 'duration_days' => 30, 'daily_rate' => 0.001, 'min_amount' => 100, 'max_amount' => 499, 'description' => 'Entry'],
            ['name' => 'Growth', 'duration_days' => 90, 'daily_rate' => 0.0012, 'min_amount' => 500, 'max_amount' => 1499, 'description' => 'Most Popular'],
            ['name' => 'Advantage', 'duration_days' => 180, 'daily_rate' => 0.0015, 'min_amount' => 1500, 'max_amount' => 4999, 'description' => 'Better Value'],
            ['name' => 'Pro', 'duration_days' => 365, 'daily_rate' => 0.0018, 'min_amount' => 5000, 'max_amount' => 9999, 'description' => 'Premium'],
            ['name' => 'Elite', 'duration_days' => 730, 'daily_rate' => 0.002, 'min_amount' => 10000, 'max_amount' => null, 'description' => 'VIP'],
        ];

        foreach ($plans as $plan) {
            InvestmentPlan::updateOrCreate(
                ['name' => $plan['name']],
                [...$plan, 'is_active' => true, 'is_custom' => false],
            );
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: reverting plan tiers is not meaningful.
    }
};
