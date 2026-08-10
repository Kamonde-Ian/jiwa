<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // deposit | withdrawal
            $table->string('network');
            $table->string('address');
            $table->decimal('balance', 18, 8)->default(0);
            $table->decimal('gas_balance', 18, 8)->default(0); // reserved for transaction fees
            $table->string('currency', 10)->default('USD');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_wallets');
    }
};
