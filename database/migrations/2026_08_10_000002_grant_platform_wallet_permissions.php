<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['view platform wallets', 'manage platform wallets'] as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        if ($admin = Role::query()->where('name', 'admin')->first()) {
            $admin->givePermissionTo(['view platform wallets', 'manage platform wallets']);
        }
    }

    public function down(): void
    {
        if ($admin = Role::query()->where('name', 'admin')->first()) {
            $admin->revokePermissionTo(['view platform wallets', 'manage platform wallets']);
        }
    }
};