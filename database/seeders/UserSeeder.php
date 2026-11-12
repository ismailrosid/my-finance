<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('role_code', 'super_admin')->first();
        $userRole       = Role::where('role_code', 'user')->first();

        // Super Admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@myfinance.test'],
            [
                'name'      => 'Super Admin',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );

        if ($superAdmin && $superAdminRole) {
            $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        // Demo User
        $demoUser = User::updateOrCreate(
            ['email' => 'user@myfinance.test'],
            [
                'name'      => 'Demo User',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );

        if ($demoUser && $userRole) {
            $demoUser->roles()->syncWithoutDetaching([$userRole->id]);
        }
    }
}

