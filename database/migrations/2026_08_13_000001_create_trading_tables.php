<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_pools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol', 20);
            $table->string('currency', 10)->default('USD');
            $table->decimal('base_nav', 16, 8)->default(100);
            $table->decimal('nav', 16, 8)->default(100); // current unit price
            $table->decimal('total_units', 20, 8)->default(0);
            $table->timestamp('nav_updated_at')->nullable();
            $table->decimal('min_allocate', 16, 2)->default(100);
            $table->decimal('max_allocate', 16, 2)->nullable();
            $table->decimal('daily_return_pct', 8, 4)->nullable(); // last settled return
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pool_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_id')->constrained('trading_pools')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('units', 20, 8);
            $table->decimal('settled_amount', 16, 2); // value at last settlement (profit swept out)
            $table->string('status')->default('active'); // active | closed
            $table->timestamp('allocated_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('trading_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_id')->constrained('trading_pools')->cascadeOnDelete();
            $table->date('session_date');
            $table->decimal('open_nav', 16, 8);
            $table->decimal('high_nav', 16, 8);
            $table->decimal('low_nav', 16, 8);
            $table->decimal('close_nav', 16, 8);
            $table->decimal('return_pct', 8, 4); // signed daily return %
            $table->decimal('pnl', 16, 2)->default(0);
            $table->boolean('is_profit')->default(true);
            $table->unsignedInteger('trade_count')->default(0);
            $table->string('strategy')->nullable();
            $table->timestamps();

            $table->unique(['pool_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_sessions');
        Schema::dropIfExists('pool_allocations');
        Schema::dropIfExists('trading_pools');
    }
};