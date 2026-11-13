<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Installment;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateDataPopupTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $adminRole;
    protected Account $account;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create([
            'role_code'   => 'super_admin',
            'role_name'   => 'Super Admin',
            'description' => 'Full access',
            'is_active'   => true,
        ]);

        $this->user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        UserRole::create([
            'user_id' => $this->user->id,
            'role_id' => $this->adminRole->id,
        ]);

        $this->account = Account::create([
            'user_id'         => $this->user->id,
            'account_name'    => 'Test Bank',
            'account_type'    => 'bank',
            'initial_balance' => 5000000,
            'current_balance' => 5000000,
            'color'           => '#10B981',
            'is_active'       => true,
        ]);

        $this->category = Category::create([
            'user_id'       => $this->user->id,
            'category_name' => 'Food & Drinks',
            'category_type' => 'expense',
            'color'         => '#EF4444',
            'is_system'     => false,
            'is_active'     => true,
        ]);
    }

    public function test_account_create_flashes_success_popup(): void
    {
        $response = $this->actingAs($this->user)->followingRedirects()->post(route('accounts.store'), [
            'account_name'    => 'BCA Savings',
            'account_type'    => 'bank',
            'initial_balance' => 1000000,
            'color'           => '#3B82F6',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Account created successfully.');
        $response->assertSee('toastManager');
    }

    public function test_transaction_create_flashes_success_popup(): void
    {
        $response = $this->actingAs($this->user)->followingRedirects()->post(route('transactions.store'), [
            'transaction_type'  => 'expense',
            'amount'            => 50000,
            'transaction_date'  => now()->toDateString(),
            'source_account_id' => $this->account->id,
            'category_id'       => $this->category->id,
            'description'       => 'Lunch at Cafe',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Transaction recorded successfully.');
        $response->assertSee('toastManager');
    }

    public function test_budget_create_flashes_success_popup(): void
    {
        $response = $this->actingAs($this->user)->followingRedirects()->post(route('budgets.store'), [
            'category_id'  => $this->category->id,
            'period_month' => now()->month,
            'period_year'  => now()->year,
            'amount_limit' => 2000000,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Budget saved successfully.');
        $response->assertSee('toastManager');
    }

    public function test_debt_create_flashes_success_popup(): void
    {
        $response = $this->actingAs($this->user)->followingRedirects()->post(route('debts.store'), [
            'creditor_name'    => 'Bank Mandiri KPR',
            'debt_type'        => 'bank_loan',
            'direction'        => 'payable',
            'principal_amount' => 12000000,
            'interest_rate'    => 5,
            'tenor_months'     => 12,
            'start_date'       => now()->toDateString(),
            'due_date'         => now()->addYear()->toDateString(),
        ]);

        $response->assertStatus(200);
        $response->assertSee('Debt recorded and installments schedule created.');
        $response->assertSee('toastManager');
    }

    public function test_category_create_flashes_success_popup(): void
    {
        $response = $this->actingAs($this->user)->followingRedirects()->post(route('settings.store-category'), [
            'category_name' => 'Side Project',
            'category_type' => 'income',
            'color'         => '#10B981',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Category created successfully.');
        $response->assertSee('toastManager');
    }

    public function test_role_create_flashes_success_popup(): void
    {
        $response = $this->actingAs($this->user)->post(route('roles.store'), [
            'role_name'   => 'Finance Auditor',
            'description' => 'Audit permissions',
        ]);

        $newRole = Role::where('role_name', 'Finance Auditor')->firstOrFail();

        $response->assertRedirect(route('roles.index', ['role_id' => $newRole->id]));
        $response->assertSessionHas('success', "Role 'Finance Auditor' created successfully.");
    }
}
