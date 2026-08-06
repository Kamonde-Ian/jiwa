<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('investment_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('rate', 6, 4);
            $table->timestamps();

            $table->unique('investment_id');
            $table->index(['referrer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_earnings');
    }
};
