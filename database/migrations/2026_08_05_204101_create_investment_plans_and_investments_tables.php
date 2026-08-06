<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('duration_days');
            $table->decimal('daily_rate', 10, 6)->default(0.005000);
            $table->decimal('min_amount', 18, 8)->default(50);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('investment_plans')->restrictOnDelete();
            $table->decimal('principal_amount', 18, 8);
            $table->decimal('daily_rate_snapshot', 10, 6);
            $table->string('status')->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('matures_at');
            $table->timestamp('last_interest_credited_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'matures_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
        Schema::dropIfExists('investment_plans');
    }
};
