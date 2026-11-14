<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Debt;
use App\Models\Installment;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlexibleLoanDebtTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'role_code'   => 'super_admin',
            'role_name'   => 'Super Admin',
            'description' => 'Full access',
            'is_active'   => true,
        ]);

        $this->user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);

        \App\Models\UserRole::create([
            'user_id' => $this->user->id,
            'role_id' => $role->id,
        ]);

        $this->account = Account::create([
            'user_id'         => $this->user->id,
            'account_name'    => 'Bank BCA',
            'account_number'  => '1234567890',
            'bank_name'       => 'BCA',
            'account_type'    => 'bank',
            'currency_code'   => 'IDR',
            'initial_balance' => 15000000,
            'current_balance' => 15000000,
            'is_active'       => true,
        ]);
    }

    public function test_can_record_scenario_a_zero_interest_with_fixed_admin_fee(): void
    {
        // Scenario A: Principal Rp1.000.000, Tenor 1 Month, Interest 0%, Admin Fee Rp50.000 -> Total Rp1.050.000
        $response = $this->actingAs($this->user)->post(route('debts.store'), [
            'creditor_name'    => 'Koperasi Karyawan',
            'debt_type'        => 'personal',
            'direction'        => 'payable',
            'principal_amount' => 1000000,
            'tenor_value'      => 1,
            'tenor_unit'       => 'months',
            'interest_rate'    => 0,
            'admin_fee_type'   => 'fixed',
            'admin_fee_value'  => 50000,
            'start_date'       => '2026-01-01',
            'due_date'         => '2026-02-01',
        ]);

        $response->assertRedirect(route('debts.index'));
        $response->assertSessionHas('success');

        $debt = Debt::where('user_id', $this->user->id)->first();
        $this->assertNotNull($debt);
        $this->assertEquals(1000000.00, (float) $debt->principal_amount);
        $this->assertEquals(0.00, (float) $debt->interest_amount);
        $this->assertEquals(50000.00, (float) $debt->fee_amount);
        $this->assertEquals(1050000.00, (float) $debt->total_amount);
        $this->assertEquals(1050000.00, (float) $debt->remaining_principal);
        $this->assertEquals(1, $debt->tenor_value);
        $this->assertEquals('months', $debt->tenor_unit);

        // Check installment schedule
        $installments = Installment::where('debt_id', $debt->id)->get();
        $this->assertCount(1, $installments);
        $this->assertEquals(1050000.00, (float) $installments[0]->amount);
        $this->assertEquals(1000000.00, (float) $installments[0]->principal_amount);
        $this->assertEquals(0.00, (float) $installments[0]->interest_amount);
        $this->assertEquals(50000.00, (float) $installments[0]->fee_amount);
    }

    public function test_can_record_scenario_b_daily_interest_with_single_lump_sum(): void
    {
        // Scenario B: Principal Rp5.000.000, Tenor 30 Days, Interest Rate 0.1% Daily Flat, Admin Fee Rp0
        // Total Interest: Rp150.000, Total Repayment: Rp5.150.000
        $response = $this->actingAs($this->user)->post(route('debts.store'), [
            'creditor_name'       => 'EasyPinjam Online',
            'debt_type'           => 'platform_online',
            'direction'           => 'payable',
            'principal_amount'    => 5000000,
            'tenor_value'         => 30,
            'tenor_unit'          => 'days',
            'repayment_frequency' => 'single',
            'interest_rate'       => 0.1,
            'interest_period'     => 'daily',
            'interest_method'     => 'flat',
            'admin_fee_type'      => 'fixed',
            'admin_fee_value'     => 0,
            'start_date'          => '2026-03-01',
            'due_date'            => '2026-03-31',
        ]);

        $response->assertRedirect(route('debts.index'));

        $debt = Debt::where('user_id', $this->user->id)->first();
        $this->assertNotNull($debt);
        $this->assertEquals(5000000.00, (float) $debt->principal_amount);
        $this->assertEquals(150000.00, (float) $debt->interest_amount);
        $this->assertEquals(0.00, (float) $debt->fee_amount);
        $this->assertEquals(5150000.00, (float) $debt->total_amount);
        $this->assertEquals(30, $debt->tenor_value);
        $this->assertEquals('days', $debt->tenor_unit);
        $this->assertEquals('single', $debt->repayment_frequency);

        $installments = Installment::where('debt_id', $debt->id)->get();
        $this->assertCount(1, $installments);
        $this->assertEquals(5150000.00, (float) $installments[0]->amount);
        $this->assertEquals(5000000.00, (float) $installments[0]->principal_amount);
        $this->assertEquals(150000.00, (float) $installments[0]->interest_amount);
    }

    public function test_can_record_scenario_c_monthly_interest_and_admin_fee(): void
    {
        // Scenario C: Principal Rp10.000.000, Tenor 6 Months, Interest 2% Monthly Flat, Admin Fee Rp100.000
        // Total Interest: Rp1.200.000, Total Repayment: Rp11.300.000
        $response = $this->actingAs($this->user)->post(route('debts.store'), [
            'creditor_name'       => 'Bank Mandiri KTA',
            'debt_type'           => 'bank_loan',
            'direction'           => 'payable',
            'principal_amount'    => 10000000,
            'tenor_value'         => 6,
            'tenor_unit'          => 'months',
            'repayment_frequency' => 'monthly',
            'interest_rate'       => 2,
            'interest_period'     => 'monthly',
            'interest_method'     => 'flat',
            'admin_fee_type'      => 'fixed',
            'admin_fee_value'     => 100000,
            'start_date'          => '2026-01-01',
            'due_date'            => '2026-07-01',
        ]);

        $response->assertRedirect(route('debts.index'));

        $debt = Debt::where('user_id', $this->user->id)->first();
        $this->assertEquals(10000000.00, (float) $debt->principal_amount);
        $this->assertEquals(1200000.00, (float) $debt->interest_amount);
        $this->assertEquals(100000.00, (float) $debt->fee_amount);
        $this->assertEquals(11300000.00, (float) $debt->total_amount);

        // Check installment schedule
        $installments = Installment::where('debt_id', $debt->id)->orderBy('installment_number')->get();
        $this->assertCount(6, $installments);

        // Total of all installments must equal 11.300.000
        $this->assertEquals(11300000.00, (float) $installments->sum('amount'));

        // Admin fee is only in 1st installment
        $this->assertEquals(100000.00, (float) $installments[0]->fee_amount);
        for ($i = 1; $i < 6; $i++) {
            $this->assertEquals(0.00, (float) $installments[$i]->fee_amount);
        }
    }

    public function test_percentage_admin_fee_calculation(): void
    {
        // 1.5% admin fee on 20.000.000 = 300.000 fee
        $response = $this->actingAs($this->user)->post(route('debts.store'), [
            'creditor_name'       => 'Bank BCA Personal',
            'debt_type'           => 'bank_loan',
            'direction'           => 'payable',
            'principal_amount'    => 20000000,
            'tenor_value'         => 1,
            'tenor_unit'          => 'years',
            'repayment_frequency' => 'monthly',
            'interest_rate'       => 0,
            'admin_fee_type'      => 'percentage',
            'admin_fee_value'     => 1.5,
            'start_date'          => '2026-01-01',
            'due_date'            => '2027-01-01',
        ]);

        $response->assertRedirect(route('debts.index'));

        $debt = Debt::where('user_id', $this->user->id)->first();
        $this->assertEquals(300000.00, (float) $debt->fee_amount);
        $this->assertEquals('percentage', $debt->admin_fee_type);
        $this->assertEquals(1.5, (float) $debt->admin_fee_value);
        $this->assertEquals(20300000.00, (float) $debt->total_amount);
    }

    public function test_tenor_in_weeks(): void
    {
        $response = $this->actingAs($this->user)->post(route('debts.store'), [
            'creditor_name'       => 'Weekly Cash',
            'debt_type'           => 'personal',
            'direction'           => 'payable',
            'principal_amount'    => 4000000,
            'tenor_value'         => 4,
            'tenor_unit'          => 'weeks',
            'repayment_frequency' => 'weekly',
            'interest_rate'       => 0,
            'admin_fee_value'     => 0,
            'start_date'          => '2026-06-01',
            'due_date'            => '2026-06-29',
        ]);

        $response->assertRedirect(route('debts.index'));

        $debt = Debt::where('user_id', $this->user->id)->first();
        $this->assertEquals(4, $debt->tenor_value);
        $this->assertEquals('weeks', $debt->tenor_unit);

        $installments = Installment::where('debt_id', $debt->id)->get();
        $this->assertCount(4, $installments);
        $this->assertEquals(4000000.00, (float) $installments->sum('amount'));
    }

    public function test_backward_compatibility_with_legacy_payload(): void
    {
        // Legacy payload only has tenor_months, interest_rate as annual, and fee_amount
        $response = $this->actingAs($this->user)->post(route('debts.store'), [
            'creditor_name'    => 'Bank Mandiri Legacy',
            'debt_type'        => 'bank_loan',
            'direction'        => 'payable',
            'principal_amount' => 12000000,
            'interest_rate'    => 5,
            'tenor_months'     => 12,
            'start_date'       => '2026-01-01',
            'due_date'         => '2027-01-01',
        ]);

        $response->assertRedirect(route('debts.index'));

        $debt = Debt::where('user_id', $this->user->id)->first();
        $this->assertNotNull($debt);
        // Simple annual interest: 12.000.000 * 5% * (12/12) = 600.000
        $this->assertEquals(600000.00, (float) $debt->interest_amount);
        $this->assertEquals(12600000.00, (float) $debt->total_amount);
        $this->assertEquals(12, $debt->tenor_value);
        $this->assertEquals('months', $debt->tenor_unit);
        $this->assertEquals('annual', $debt->interest_period);
        $this->assertEquals('flat', $debt->interest_method);

        // Formatted helpers
        $this->assertEquals('12 Months', $debt->formatted_tenor);
        $this->assertStringContainsString('5%', $debt->formatted_interest);
    }

    public function test_calculate_preview_endpoint(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('debts.calculate'), [
            'principal_amount'    => 10000000,
            'tenor_value'         => 6,
            'tenor_unit'          => 'months',
            'interest_rate'       => 2,
            'interest_period'     => 'monthly',
            'interest_method'     => 'flat',
            'admin_fee_type'      => 'fixed',
            'admin_fee_value'     => 100000,
            'repayment_frequency' => 'monthly',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'principal_amount' => 10000000,
            'interest_amount'  => 1200000,
            'fee_amount'       => 100000,
            'total_amount'     => 11300000,
        ]);
    }

    public function test_paying_installment_deducts_account_and_updates_debt_without_double_counting(): void
    {
        // Create debt and installment
        $debt = Debt::create([
            'user_id'             => $this->user->id,
            'debt_code'           => 'DBT-PAY01',
            'creditor_name'       => 'Bank BCA KTA',
            'debt_type'           => 'bank_loan',
            'direction'           => 'payable',
            'principal_amount'    => 10000000.00,
            'interest_rate'       => 0,
            'fee_amount'          => 50000.00,
            'total_amount'        => 10050000.00,
            'remaining_principal' => 10050000.00,
            'start_date'          => '2026-01-01',
            'due_date'            => '2026-02-01',
            'tenor_value'         => 1,
            'tenor_unit'          => 'months',
            'status'              => 'active',
        ]);

        $installment = Installment::create([
            'debt_id'            => $debt->id,
            'installment_number' => 1,
            'due_date'           => '2026-02-01',
            'amount'             => 10050000.00,
            'principal_amount'   => 10000000.00,
            'interest_amount'    => 0.00,
            'fee_amount'         => 50000.00,
            'paid_amount'        => 0.00,
            'status'             => 'unpaid',
        ]);

        $initialBalance = (float) $this->account->current_balance;

        $response = $this->actingAs($this->user)->post(route('debts.pay-installment', [$debt->id, $installment->id]), [
            'account_id'  => $this->account->id,
            'amount_paid' => 10050000.00,
            'notes'       => 'Paid off loan',
        ]);

        $response->assertRedirect(route('debts.index'));

        // Account balance deducted
        $this->account->refresh();
        $this->assertEquals($initialBalance - 10050000.00, (float) $this->account->current_balance);

        // Installment marked paid
        $installment->refresh();
        $this->assertEquals('paid', $installment->status);
        $this->assertEquals(10050000.00, (float) $installment->paid_amount);

        // Debt marked paid_off and remaining balance 0
        $debt->refresh();
        $this->assertEquals('paid_off', $debt->status);
        $this->assertEquals(0.00, (float) $debt->remaining_principal);

        // Verify that only a debt_payment transaction was created (no duplicate expense)
        $txns = Transaction::where('debt_id', $debt->id)->get();
        $this->assertCount(1, $txns);
        $this->assertEquals('debt_payment', $txns[0]->transaction_type);
        $this->assertEquals(10050000.00, (float) $txns[0]->amount);

        // Ensure no expense transaction was created for this debt payment
        $expenseTxns = Transaction::where('debt_id', $debt->id)->where('transaction_type', 'expense')->count();
        $this->assertEquals(0, $expenseTxns);
    }
}
