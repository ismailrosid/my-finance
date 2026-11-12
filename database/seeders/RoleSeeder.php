<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'role_code'   => 'super_admin',
                'role_name'   => 'Super Admin',
                'description' => 'Full access to all features and configurations.',
                'is_active'   => true,
            ],
            [
                'role_code'   => 'admin',
                'role_name'   => 'Admin',
                'description' => 'Access to operational features based on assigned permissions.',
                'is_active'   => true,
            ],
            [
                'role_code'   => 'user',
                'role_name'   => 'User',
                'description' => 'Access to personal financial data only.',
                'is_active'   => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['role_code' => $role['role_code']], $role);
        }
    }
}

