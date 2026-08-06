<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('network', 20);
            $table->string('currency', 10);
            $table->string('tx_hash', 255);
            $table->decimal('amount_currency', 18, 8)->nullable();
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->unique('tx_hash');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
