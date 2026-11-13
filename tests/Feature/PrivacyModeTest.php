<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Installment;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenu;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrivacyModeTest extends TestCase
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
            'account_name'    => 'Test Bank BCA',
            'account_type'    => 'bank',
            'initial_balance' => 10000000,
            'current_balance' => 10000000,
            'color'           => '#10B981',
            'is_active'       => true,
        ]);

        $this->category = Category::create([
            'user_id'       => $this->user->id,
            'category_name' => 'F&B',
            'category_type' => 'expense',
            'color'         => '#EF4444',
            'is_system'     => false,
            'is_active'     => true,
        ]);
    }

    public function test_header_contains_privacy_mode_toggle_button(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Toggle Privacy Mode');
        $response->assertSee('$store.privacy?.toggle()', false);
        $response->assertSee('privacy-blur');
    }

    public function test_dashboard_renders_privacy_blur_classes(): void
    {
        Transaction::create([
            'user_id'           => $this->user->id,
            'source_account_id' => $this->account->id,
            'category_id'       => $this->category->id,
            'transaction_code'  => 'TXN-001',
            'transaction_type'  => 'expense',
            'amount'            => 150000,
            'transaction_date'  => now()->toDateString(),
            'description'       => 'Lunch meeting',
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('class="privacy-blur"', false);
    }

    public function test_accounts_page_renders_privacy_blur(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounts.index'));

        $response->assertStatus(200);
        $response->assertSee('class="privacy-blur"', false);
    }

    public function test_transactions_page_renders_privacy_blur(): void
    {
        Transaction::create([
            'user_id'           => $this->user->id,
            'source_account_id' => $this->account->id,
            'category_id'       => $this->category->id,
            'transaction_code'  => 'TXN-002',
            'transaction_type'  => 'expense',
            'amount'            => 250000,
            'transaction_date'  => now()->toDateString(),
            'description'       => 'Team Dinner',
        ]);

        $response = $this->actingAs($this->user)->get(route('transactions.index'));

        $response->assertStatus(200);
        $response->assertSee('class="privacy-blur"', false);
    }

    public function test_debts_page_renders_privacy_blur(): void
    {
        $response = $this->actingAs($this->user)->get(route('debts.index'));

        $response->assertStatus(200);
        $response->assertSee('class="privacy-blur"', false);
    }

    public function test_budgets_page_renders_privacy_blur(): void
    {
        $response = $this->actingAs($this->user)->get(route('budgets.index'));

        $response->assertStatus(200);
        $response->assertSee('class="privacy-blur"', false);
    }

    public function test_reports_page_renders_privacy_blur(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertSee('class="privacy-blur"', false);
    }
}
