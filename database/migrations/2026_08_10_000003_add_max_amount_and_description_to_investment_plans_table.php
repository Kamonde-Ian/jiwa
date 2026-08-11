<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_plans', function (Blueprint $table) {
            $table->decimal('max_amount', 18, 8)->nullable()->after('min_amount');
            $table->string('description')->nullable()->after('max_amount');
        });
    }

    public function down(): void
    {
        Schema::table('investment_plans', function (Blueprint $table) {
            $table->dropColumn(['max_amount', 'description']);
        });
    }
};
