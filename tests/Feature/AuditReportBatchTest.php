<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\ReportBatch;
use App\Models\Role;
use App\Models\RoleMenu;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditReportBatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;
    protected Role $superAdminRole;
    protected Role $userRole;
    protected Menu $auditMenu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::create([
            'role_code'   => 'super_admin',
            'role_name'   => 'Super Admin',
            'description' => 'Full access',
            'is_active'   => true,
        ]);

        $this->userRole = Role::create([
            'role_code'   => 'user',
            'role_name'   => 'User',
            'description' => 'Standard user',
            'is_active'   => true,
        ]);

        $this->auditMenu = Menu::create([
            'menu_code'  => 'audit',
            'menu_name'  => 'Audit Reports',
            'menu_url'   => '/audit-reports',
            'menu_order' => 9,
            'is_active'  => true,
        ]);

        RoleMenu::create([
            'role_id'    => $this->superAdminRole->id,
            'menu_id'    => $this->auditMenu->id,
            'can_view'   => true,
            'can_create' => true,
            'can_update' => true,
            'can_delete' => true,
            'can_export' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        UserRole::create([
            'user_id' => $this->superAdmin->id,
            'role_id' => $this->superAdminRole->id,
        ]);

        $this->regularUser = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        UserRole::create([
            'user_id' => $this->regularUser->id,
            'role_id' => $this->userRole->id,
        ]);
    }

    public function test_super_admin_can_view_audit_reports_screen(): void
    {
        // Seed an audit log with before/after diff
        AuditLog::create([
            'user_id'    => $this->superAdmin->id,
            'action'     => 'UPDATE',
            'table_name' => 'accounts',
            'record_id'  => 55,
            'old_values' => ['account_name' => 'Old Bank', 'current_balance' => 1000000],
            'new_values' => ['account_name' => 'New Bank Mandiri', 'current_balance' => 1500000],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('audit-reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Audit Reports');
        $response->assertSee('Generate Batch Report');
        $response->assertSee('Report Audit Transaksi Keuangan');
        $response->assertSee('Field Yang Diedit');
        $response->assertSee('Inspect');
    }

    public function test_regular_user_cannot_access_audit_reports(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('audit-reports.index'));

        $response->assertStatus(403);
    }

    public function test_super_admin_can_generate_batch_report_with_field_diffs(): void
    {
        // Seed an audit log with an UPDATE action
        AuditLog::create([
            'user_id'    => $this->superAdmin->id,
            'action'     => 'UPDATE',
            'table_name' => 'transactions',
            'record_id'  => 101,
            'old_values' => ['amount' => 100000, 'description' => 'Original description'],
            'new_values' => ['amount' => 250000, 'description' => 'Updated description'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('audit-reports.generate'), [
            'report_type' => 'audit_transactions',
            'date_from'   => now()->subDays(7)->toDateString(),
            'date_to'     => now()->toDateString(),
        ]);

        $response->assertRedirect(route('audit-reports.index'));
        $response->assertSessionHas('success');

        $batch = ReportBatch::where('report_type', 'audit_transactions')->firstOrFail();
        $this->assertEquals('completed', $batch->status);
        $this->assertEquals(1, $batch->total_records);
        $this->assertNotNull($batch->file_path);

        $this->assertTrue(Storage::disk('local')->exists($batch->file_path));

        $csvContent = Storage::disk('local')->get($batch->file_path);
        $this->assertStringContainsString('Field Yang Diedit (Fields Modified)', $csvContent);
        $this->assertStringContainsString('Sebelum (Before / Old Value)', $csvContent);
        $this->assertStringContainsString('Sesudah (After / New Value)', $csvContent);
        $this->assertStringContainsString('Perubahan (Before ➔ After)', $csvContent);

        // Verify diff contents
        $this->assertStringContainsString('amount, description', $csvContent);
        $this->assertStringContainsString('amount: [100000] ➔ [250000]', $csvContent);
        $this->assertStringContainsString('Original description', $csvContent);
        $this->assertStringContainsString('Updated description', $csvContent);

        // Clean up file
        Storage::disk('local')->delete($batch->file_path);
    }

    public function test_super_admin_can_download_batch_report(): void
    {
        $dummyFile = 'reports/test_download.csv';
        Storage::disk('local')->put($dummyFile, "Log ID,Action\n1,CREATE");

        $batch = ReportBatch::create([
            'user_id'       => $this->superAdmin->id,
            'report_type'   => 'audit_transactions',
            'report_name'   => 'Report Audit Transaksi Keuangan',
            'file_path'     => $dummyFile,
            'file_size'     => 20,
            'total_records' => 1,
            'status'        => 'completed',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('audit-reports.download', $batch->id));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));

        if (Storage::disk('local')->exists($dummyFile)) {
            Storage::disk('local')->delete($dummyFile);
        }
    }

    public function test_super_admin_can_delete_batch_report(): void
    {
        $dummyFile = 'reports/test_delete.csv';
        Storage::disk('local')->put($dummyFile, "dummy content");

        $batch = ReportBatch::create([
            'user_id'       => $this->superAdmin->id,
            'report_type'   => 'audit_transactions',
            'report_name'   => 'Report Audit Transaksi Keuangan',
            'file_path'     => $dummyFile,
            'file_size'     => 13,
            'total_records' => 1,
            'status'        => 'completed',
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('audit-reports.destroy', $batch->id));

        $response->assertRedirect(route('audit-reports.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('report_batches', ['id' => $batch->id]);
        $this->assertFalse(Storage::disk('local')->exists($dummyFile));
    }
}
