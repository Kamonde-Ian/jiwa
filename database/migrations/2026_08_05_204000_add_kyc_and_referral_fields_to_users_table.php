<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email_verified_at');
            $table->string('country')->nullable()->after('phone');
            $table->string('referral_code', 16)->nullable()->unique()->after('country');
            $table->foreignId('referred_by')->nullable()->after('referral_code')
                ->constrained('users')->nullOnDelete();
            $table->string('kyc_status')->default('unverified')->after('referred_by');
            $table->boolean('two_factor_enabled')->default(false)->after('kyc_status');
            $table->text('google2fa_secret')->nullable()->after('two_factor_enabled');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropUnique(['referral_code']);
            $table->dropColumn([
                'phone', 'country', 'referral_code', 'referred_by',
                'kyc_status', 'two_factor_enabled', 'google2fa_secret', 'deleted_at',
            ]);
        });
    }
};
