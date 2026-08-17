<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'view admin earnings']);

        if ($admin = Role::query()->where('name', 'admin')->first()) {
            $admin->givePermissionTo('view admin earnings');
        }
    }

    public function down(): void
    {
        if ($admin = Role::query()->where('name', 'admin')->first()) {
            $admin->revokePermissionTo('view admin earnings');
        }
    }
};