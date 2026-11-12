<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenu;
use Illuminate\Database\Seeder;

class RoleMenuSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::where('role_code', 'super_admin')->first();
        $admin      = Role::where('role_code', 'admin')->first();
        $user       = Role::where('role_code', 'user')->first();

        $menus = Menu::all()->keyBy('menu_code');

        // Super Admin: full access to everything
        foreach ($menus as $menu) {
            RoleMenu::updateOrCreate(
                ['role_id' => $superAdmin->id, 'menu_id' => $menu->id],
                [
                    'can_view'   => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'can_export' => true,
                ]
            );
        }

        // Admin: full access except settings
        $adminMenus = ['dashboard', 'transactions', 'accounts', 'debts', 'budgets', 'reports'];
        foreach ($adminMenus as $menuCode) {
            if (isset($menus[$menuCode])) {
                RoleMenu::updateOrCreate(
                    ['role_id' => $admin->id, 'menu_id' => $menus[$menuCode]->id],
                    [
                        'can_view'   => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => true,
                        'can_export' => true,
                    ]
                );
            }
        }

        // User: standard access — view/create/update/export, no delete on most
        $userMenuPermissions = [
            'dashboard'    => ['can_view' => true,  'can_create' => false, 'can_update' => false, 'can_delete' => false, 'can_export' => false],
            'transactions' => ['can_view' => true,  'can_create' => true,  'can_update' => true,  'can_delete' => false, 'can_export' => true],
            'accounts'     => ['can_view' => true,  'can_create' => true,  'can_update' => true,  'can_delete' => false, 'can_export' => false],
            'debts'        => ['can_view' => true,  'can_create' => true,  'can_update' => true,  'can_delete' => false, 'can_export' => false],
            'budgets'      => ['can_view' => true,  'can_create' => true,  'can_update' => true,  'can_delete' => true,  'can_export' => false],
            'reports'      => ['can_view' => true,  'can_create' => false, 'can_update' => false, 'can_delete' => false, 'can_export' => true],
            'settings'     => ['can_view' => true,  'can_create' => false, 'can_update' => true,  'can_delete' => false, 'can_export' => false],
        ];

        foreach ($userMenuPermissions as $menuCode => $perms) {
            if (isset($menus[$menuCode])) {
                RoleMenu::updateOrCreate(
                    ['role_id' => $user->id, 'menu_id' => $menus[$menuCode]->id],
                    $perms
                );
            }
        }
    }
}

