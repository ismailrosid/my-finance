<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Debt;
use App\Models\RevisionInfo;
use App\Models\Menu;
use App\Models\ReportBatch;
use App\Models\Role;
use App\Models\RoleMenu;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnversAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $role = Role::create([
            'role_code'   => 'super_admin',
            'role_name'   => 'Super Admin',
            'description' => 'Full access',
            'is_active'   => true,
        ]);

        $menu = Menu::create([
            'menu_code'  => 'audit',
            'menu_name'  => 'Audit Reports',
            'menu_url'   => '/audit-reports',
            'menu_order' => 9,
            'is_active'  => true,
        ]);

        RoleMenu::create([
            'role_id'    => $role->id,
            'menu_id'    => $menu->id,
            'can_view'   => true,
            'can_create' => true,
            'can_update' => true,
            'can_delete' => true,
            'can_export' => true,
        ]);

        UserRole::create([
            'user_id' => $this->user->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_account_lifecycle_creates_envers_revisions(): void
    {
        $this->actingAs($this->user);

        // 1. CREATE -> revision_type = 0
        $account = Account::create([
            'user_id'         => $this->user->id,
            'account_name'    => 'BCA Utama',
            'account_type'    => 'bank',
            'account_number'  => '1234567890',
            'bank_name'       => 'Bank Central Asia',
            'currency_code'   => 'IDR',
            'initial_balance' => 1000000,
            'current_balance' => 1000000,
            'is_active'       => true,
        ]);

        $createAudit = DB::table('accounts_aud')
            ->where('id', $account->id)
            ->where('revision_type', RevisionInfo::TYPE_CREATE)
            ->first();

        $this->assertNotNull($createAudit, 'accounts_aud must record CREATE snapshot');
        $this->assertEquals(0, $createAudit->revision_type);
        $this->assertEquals('BCA Utama', $createAudit->account_name);
        $this->assertEquals($account->id, $createAudit->id);

        $revision1 = RevisionInfo::find($createAudit->revision_id);
        $this->assertNotNull($revision1);
        $this->assertEquals($this->user->id, $revision1->user_id);

        // 2. UPDATE -> revision_type = 1
        $account->update([
            'account_name'    => 'BCA Tabungan Bisnis',
            'current_balance' => 2500000,
        ]);

        $updateAudit = DB::table('accounts_aud')
            ->where('id', $account->id)
            ->where('revision_type', RevisionInfo::TYPE_UPDATE)
            ->first();

        $this->assertNotNull($updateAudit, 'accounts_aud must record UPDATE snapshot');
        $this->assertEquals(1, $updateAudit->revision_type);
        $this->assertEquals('BCA Tabungan Bisnis', $updateAudit->account_name);
        $this->assertEquals(2500000, $updateAudit->current_balance);

        $revision2 = RevisionInfo::find($updateAudit->revision_id);
        $this->assertNotNull($revision2);
        $this->assertNotEquals($revision1->id, $revision2->id);

        // 3. DELETE -> revision_type = 2
        $account->delete();

        $deleteAudit = DB::table('accounts_aud')
            ->where('id', $account->id)
            ->where('revision_type', RevisionInfo::TYPE_DELETE)
            ->first();

        $this->assertNotNull($deleteAudit, 'accounts_aud must record DELETE snapshot');
        $this->assertEquals(2, $deleteAudit->revision_type);

        $history = $account->auditHistory();
        $this->assertCount(3, $history);
        $this->assertEquals(RevisionInfo::TYPE_DELETE, $history[0]->revision_type);
        $this->assertEquals(RevisionInfo::TYPE_UPDATE, $history[1]->revision_type);
        $this->assertEquals(RevisionInfo::TYPE_CREATE, $history[2]->revision_type);
    }

    public function test_transaction_writes_to_transactions_aud(): void
    {
        $this->actingAs($this->user);

        $account = Account::create([
            'user_id'         => $this->user->id,
            'account_name'    => 'Mandiri',
            'account_type'    => 'bank',
            'currency_code'   => 'IDR',
            'initial_balance' => 500000,
            'current_balance' => 500000,
            'is_active'       => true,
        ]);

        $transaction = Transaction::create([
            'user_id'           => $this->user->id,
            'transaction_code'  => 'TX-ENVERS-001',
            'transaction_type'  => 'EXPENSE',
            'source_account_id' => $account->id,
            'amount'            => 75000,
            'transaction_date'  => '2026-11-13',
            'description'       => 'Makan Siang Envers',
        ]);

        $aud = DB::table('transactions_aud')->where('id', $transaction->id)->first();
        $this->assertNotNull($aud);
        $this->assertEquals(0, $aud->revision_type);
        $this->assertEquals('TX-ENVERS-001', $aud->transaction_code);
        $this->assertEquals(75000, $aud->amount);

        // Update
        $transaction->update(['amount' => 85000]);
        $audUpdate = DB::table('transactions_aud')
            ->where('id', $transaction->id)
            ->where('revision_type', 1)
            ->first();
        $this->assertNotNull($audUpdate);
        $this->assertEquals(85000, $audUpdate->amount);

        // Delete
        $transaction->delete();
        $audDelete = DB::table('transactions_aud')
            ->where('id', $transaction->id)
            ->where('revision_type', 2)
            ->first();
        $this->assertNotNull($audDelete);
    }

    public function test_debt_budget_and_category_write_to_aud_tables(): void
    {
        $this->actingAs($this->user);

        // Debt
        $debt = Debt::create([
            'user_id'          => $this->user->id,
            'debt_code'        => 'DBT-001',
            'debt_type'        => 'LOAN',
            'direction'        => 'PAYABLE',
            'creditor_name'    => 'Bank Envers',
            'principal_amount' => 10000000,
            'total_amount'     => 10000000,
            'remaining_principal' => 10000000,
            'start_date'       => '2026-01-01',
            'due_date'         => '2026-12-31',
            'status'           => 'ACTIVE',
        ]);
        $this->assertDatabaseHas('debts_aud', [
            'id'            => $debt->id,
            'revision_type' => 0,
            'creditor_name' => 'Bank Envers',
        ]);

        // Category
        $category = Category::create([
            'user_id'       => $this->user->id,
            'category_name' => 'Kategori Envers',
            'category_type' => 'EXPENSE',
            'is_active'     => true,
        ]);
        $this->assertDatabaseHas('categories_aud', [
            'id'            => $category->id,
            'revision_type' => 0,
            'category_name' => 'Kategori Envers',
        ]);

        // Budget
        $budget = Budget::create([
            'user_id'      => $this->user->id,
            'category_id'  => $category->id,
            'period_month' => 11,
            'period_year'  => 2026,
            'amount_limit' => 3000000,
        ]);
        $this->assertDatabaseHas('budgets_aud', [
            'id'            => $budget->id,
            'revision_type' => 0,
            'amount_limit'  => 3000000,
        ]);
    }

    public function test_without_auditing_disables_revision_logging(): void
    {
        $account = Account::withoutAuditing(function () {
            return Account::create([
                'user_id'         => $this->user->id,
                'account_name'    => 'Silent Account',
                'account_type'    => 'bank',
                'currency_code'   => 'IDR',
                'initial_balance' => 10000,
                'current_balance' => 10000,
                'is_active'       => true,
            ]);
        });

        $this->assertDatabaseMissing('accounts_aud', [
            'id' => $account->id,
        ]);
    }

    public function test_can_generate_shadow_audit_batch_report(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        // Create an account and update it to generate revisions
        $account = Account::create([
            'user_id'         => $this->user->id,
            'account_name'    => 'BSI Syariah',
            'account_type'    => 'bank',
            'currency_code'   => 'IDR',
            'initial_balance' => 2000000,
            'current_balance' => 2000000,
            'is_active'       => true,
        ]);
        $account->update(['current_balance' => 3000000]);

        $response = $this->post(route('audit-reports.generate'), [
            'report_type' => 'audit_revisions',
        ]);

        $response->assertRedirect(route('audit-reports.index'));
        $batch = ReportBatch::where('report_type', 'audit_revisions')->first();
        $this->assertNotNull($batch);
        $this->assertEquals('completed', $batch->status);
        $this->assertGreaterThan(0, $batch->total_records);
        $this->assertTrue(Storage::disk('local')->exists($batch->file_path));

        $content = Storage::disk('local')->get($batch->file_path);
        $this->assertStringContainsString('Tabel Shadow', $content);
        $this->assertStringContainsString('0 - CREATE (Tambah)', $content);
        $this->assertStringContainsString('1 - UPDATE (Ubah)', $content);
        $this->assertStringContainsString('BSI Syariah', $content);
    }
}
