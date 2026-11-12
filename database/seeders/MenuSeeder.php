<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Top-level menus
        $menus = [
            [
                'parent_id'  => null,
                'menu_code'  => 'dashboard',
                'menu_name'  => 'Dashboard',
                'menu_url'   => '/dashboard',
                'menu_icon'  => 'heroicon-o-home',
                'menu_order' => 1,
                'is_active'  => true,
            ],
            [
                'parent_id'  => null,
                'menu_code'  => 'transactions',
                'menu_name'  => 'Transactions',
                'menu_url'   => '/transactions',
                'menu_icon'  => 'heroicon-o-arrow-path',
                'menu_order' => 2,
                'is_active'  => true,
            ],
            [
                'parent_id'  => null,
                'menu_code'  => 'accounts',
                'menu_name'  => 'Accounts',
                'menu_url'   => '/accounts',
                'menu_icon'  => 'heroicon-o-credit-card',
                'menu_order' => 3,
                'is_active'  => true,
            ],
            [
                'parent_id'  => null,
                'menu_code'  => 'debts',
                'menu_name'  => 'Debts & Installments',
                'menu_url'   => '/debts',
                'menu_icon'  => 'heroicon-o-banknotes',
                'menu_order' => 4,
                'is_active'  => true,
            ],
            [
                'parent_id'  => null,
                'menu_code'  => 'budgets',
                'menu_name'  => 'Budgets',
                'menu_url'   => '/budgets',
                'menu_icon'  => 'heroicon-o-calculator',
                'menu_order' => 5,
                'is_active'  => true,
            ],
            [
                'parent_id'  => null,
                'menu_code'  => 'reports',
                'menu_name'  => 'Financial Reports',
                'menu_url'   => '/reports',
                'menu_icon'  => 'heroicon-o-chart-bar',
                'menu_order' => 6,
                'is_active'  => true,
            ],
            [
                'parent_id'  => null,
                'menu_code'  => 'settings',
                'menu_name'  => 'Settings',
                'menu_url'   => '/settings',
                'menu_icon'  => 'heroicon-o-cog-6-tooth',
                'menu_order' => 7,
                'is_active'  => true,
            ],
            [
                'parent_id'  => null,
                'menu_code'  => 'roles',
                'menu_name'  => 'Roles & Access',
                'menu_url'   => '/roles',
                'menu_icon'  => 'heroicon-o-shield-check',
                'menu_order' => 8,
                'is_active'  => true,
            ],
        ];

        foreach ($menus as $menu) {
            Menu::updateOrCreate(['menu_code' => $menu['menu_code']], $menu);
        }
    }
}

