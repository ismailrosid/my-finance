<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Income categories
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Salary',
                'category_type' => 'income',
                'color'         => '#10B981',
                'icon'          => 'heroicon-o-currency-dollar',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Freelance',
                'category_type' => 'income',
                'color'         => '#059669',
                'icon'          => 'heroicon-o-briefcase',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Investment Return',
                'category_type' => 'income',
                'color'         => '#0D9488',
                'icon'          => 'heroicon-o-chart-line',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Business Income',
                'category_type' => 'income',
                'color'         => '#0891B2',
                'icon'          => 'heroicon-o-building-storefront',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Other Income',
                'category_type' => 'income',
                'color'         => '#6366F1',
                'icon'          => 'heroicon-o-plus-circle',
                'is_system'     => true,
                'is_active'     => true,
            ],

            // Expense categories
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Food & Dining',
                'category_type' => 'expense',
                'color'         => '#F59E0B',
                'icon'          => 'heroicon-o-cake',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Transportation',
                'category_type' => 'expense',
                'color'         => '#3B82F6',
                'icon'          => 'heroicon-o-truck',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Shopping',
                'category_type' => 'expense',
                'color'         => '#EC4899',
                'icon'          => 'heroicon-o-shopping-bag',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Bills & Utilities',
                'category_type' => 'expense',
                'color'         => '#EF4444',
                'icon'          => 'heroicon-o-document-text',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Health & Medical',
                'category_type' => 'expense',
                'color'         => '#14B8A6',
                'icon'          => 'heroicon-o-heart',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Entertainment',
                'category_type' => 'expense',
                'color'         => '#8B5CF6',
                'icon'          => 'heroicon-o-film',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Education',
                'category_type' => 'expense',
                'color'         => '#0EA5E9',
                'icon'          => 'heroicon-o-academic-cap',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Housing & Rent',
                'category_type' => 'expense',
                'color'         => '#78716C',
                'icon'          => 'heroicon-o-home',
                'is_system'     => true,
                'is_active'     => true,
            ],
            [
                'user_id'       => null,
                'parent_id'     => null,
                'category_name' => 'Other Expense',
                'category_type' => 'expense',
                'color'         => '#6B7280',
                'icon'          => 'heroicon-o-ellipsis-horizontal-circle',
                'is_system'     => true,
                'is_active'     => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'category_name' => $category['category_name'],
                    'category_type' => $category['category_type'],
                    'is_system'     => true,
                    'user_id'       => null,
                ],
                $category
            );
        }
    }
}

