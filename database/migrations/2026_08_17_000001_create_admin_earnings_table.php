<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->decimal('amount', 15, 2);
            $table->decimal('rate', 6, 4);
            $table->timestamps();
            $table->unique(['admin_id', 'source_type', 'source_id']);
            $table->index(['source_type', 'source_id']);
            $table->index(['admin_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_earnings');
    }
};
