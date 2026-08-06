<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public const ADMIN_PERMISSIONS = [
        'view users',
        'manage users',
        'review kyc',
        'view investment plans',
        'manage investment plans',
        'view investments',
        'view wallet transactions',
        'view deposits',
        'review deposits',
        'view withdrawals',
        'process withdrawals',
        'view referral earnings',
        'view settings',
        'manage settings',
        'manage roles',
    ];

    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $user = Role::firstOrCreate(['name' => 'user']);

        foreach (self::ADMIN_PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin->syncPermissions(self::ADMIN_PERMISSIONS);
        $user->syncPermissions([]);
    }
}
