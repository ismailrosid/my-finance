<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenu;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'role_code' => 'user',
            'role_name' => 'User',
            'is_active' => true,
        ]);

        // Seed menus and permissions for 'user' role
        $menuCodes = ['dashboard', 'transactions', 'accounts', 'debts', 'budgets', 'reports', 'settings'];
        foreach ($menuCodes as $code) {
            $menu = Menu::create([
                'menu_code' => $code,
                'menu_name' => ucfirst($code),
                'menu_url'  => '/' . $code,
                'is_active' => true,
            ]);
            RoleMenu::create([
                'role_id'    => $role->id,
                'menu_id'    => $menu->id,
                'can_view'   => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'can_export' => false,
            ]);
        }

        $this->user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        UserRole::create([
            'user_id' => $this->user->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_settings_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Profile Settings');
        $response->assertSee('Categories');
        $response->assertSee('Security & Password', false);
    }

    public function test_store_category_redirects_to_categories_tab_with_success(): void
    {
        $response = $this->actingAs($this->user)->post(route('settings.store-category'), [
            'category_name' => 'Freelance Work',
            'category_type' => 'income',
            'color'         => '#10B981',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'categories']));
        $response->assertSessionHas('success', 'Category created successfully.');

        $this->assertDatabaseHas('categories', [
            'user_id'       => $this->user->id,
            'category_name' => 'Freelance Work',
            'category_type' => 'income',
        ]);
    }

    public function test_destroy_category_redirects_to_categories_tab_with_success(): void
    {
        $category = Category::create([
            'user_id'       => $this->user->id,
            'category_name' => 'Old Category',
            'category_type' => 'expense',
            'color'         => '#EF4444',
            'is_system'     => false,
            'is_active'     => true,
        ]);

        $response = $this->actingAs($this->user)->delete(route('settings.destroy-category', $category->id));

        $response->assertRedirect(route('settings.index', ['tab' => 'categories']));
        $response->assertSessionHas('success', 'Category deleted successfully.');

        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_update_profile_redirects_to_profile_tab_with_success(): void
    {
        $response = $this->actingAs($this->user)->put(route('settings.update-profile'), [
            'name'         => 'Updated Name',
            'phone_number' => '081299998888',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'profile']));
        $response->assertSessionHas('success', 'Profile updated successfully.');

        $this->assertDatabaseHas('users', [
            'id'           => $this->user->id,
            'name'         => 'Updated Name',
            'phone_number' => '081299998888',
        ]);
    }

    public function test_update_password_redirects_to_security_tab_with_success(): void
    {
        $response = $this->actingAs($this->user)->put(route('settings.update-password'), [
            'current_password'      => 'password123',
            'password'              => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'security']));
        $response->assertSessionHas('success', 'Password updated successfully.');

        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->user->fresh()->password));
    }

    public function test_update_password_with_wrong_current_password_redirects_to_security_tab(): void
    {
        $response = $this->actingAs($this->user)->put(route('settings.update-password'), [
            'current_password'      => 'wrongpassword',
            'password'              => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'security']));
        $response->assertSessionHasErrors(['current_password']);
    }
}
