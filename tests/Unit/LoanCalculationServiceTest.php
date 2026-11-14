<?php

namespace Tests\Unit;

use App\Models\Debt;
use App\Models\Installment;
use App\Models\User;
use App\Services\LoanCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LoanCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LoanCalculationService();
    }

    public function test_calculate_due_date_with_various_tenor_units(): void
    {
        $start = Carbon::parse('2026-01-01');

        // Days
        $dueDays = $this->service->calculateDueDate($start, 14, 'days');
        $this->assertEquals('2026-01-15', $dueDays->toDateString());

        // Weeks
        $dueWeeks = $this->service->calculateDueDate($start, 2, 'weeks');
        $this->assertEquals('2026-01-15', $dueWeeks->toDateString());

        // Months
        $dueMonths = $this->service->calculateDueDate($start, 6, 'months');
        $this->assertEquals('2026-07-01', $dueMonths->toDateString());

        // Years
        $dueYears = $this->service->calculateDueDate($start, 2, 'years');
        $this->assertEquals('2028-01-01', $dueYears->toDateString());
    }

    public function test_due_date_considers_leap_years_and_month_boundaries(): void
    {
        // 2024 is a leap year (Feb has 29 days)
        $leapStart = Carbon::parse('2024-01-31');
        $due1MoLeap = $this->service->calculateDueDate($leapStart, 1, 'months');
        $this->assertEquals('2024-02-29', $due1MoLeap->toDateString());

        // 2023 is not a leap year (Feb has 28 days)
        $nonLeapStart = Carbon::parse('2023-01-31');
        $due1MoNonLeap = $this->service->calculateDueDate($nonLeapStart, 1, 'months');
        $this->assertEquals('2023-02-28', $due1MoNonLeap->toDateString());

        // Leap year Feb 29 + 1 year => Feb 28 of non-leap year
        $feb29 = Carbon::parse('2024-02-29');
        $due1Yr = $this->service->calculateDueDate($feb29, 1, 'years');
        $this->assertEquals('2025-02-28', $due1Yr->toDateString());
    }

    public function test_calculate_admin_fee_fixed_and_percentage(): void
    {
        // Fixed amount
        $feeFixed = $this->service->calculateAdminFee(5000000, 'fixed', 50000);
        $this->assertEquals(50000.00, $feeFixed);

        // Percentage (2% of 10.000.000 = 200.000)
        $feePct = $this->service->calculateAdminFee(10000000, 'percentage', 2);
        $this->assertEquals(200000.00, $feePct);

        // Zero / negative
        $this->assertEquals(0.00, $this->service->calculateAdminFee(1000000, 'fixed', 0));
        $this->assertEquals(0.00, $this->service->calculateAdminFee(1000000, 'percentage', 0));
    }

    public function test_scenario_a_zero_interest_loan_with_admin_fee(): void
    {
        // Scenario A – Pinjaman tanpa bunga dengan biaya admin
        // Principal: Rp1.000.000
        // Tenor: 1 Month
        // Interest Rate: 0%
        // Administration Fee: Rp50.000
        // Total Repayment: Rp1.050.000
        $result = $this->service->calculateLoan([
            'principal_amount' => 1000000,
            'tenor_value'      => 1,
            'tenor_unit'       => 'months',
            'interest_rate'    => 0,
            'admin_fee_type'   => 'fixed',
            'admin_fee_value'  => 50000,
        ]);

        $this->assertEquals(1000000.00, $result['principal_amount']);
        $this->assertEquals(0.00, $result['interest_amount']);
        $this->assertEquals(50000.00, $result['fee_amount']);
        $this->assertEquals(1050000.00, $result['total_amount']);
    }

    public function test_scenario_b_loan_with_daily_interest(): void
    {
        // Scenario B – Pinjaman dengan bunga harian
        // Principal: Rp5.000.000
        // Tenor: 30 Days
        // Interest Rate: 0,1% Daily
        // Interest Method: Flat
        // Administration Fee: Rp0
        // Total Interest: Rp150.000
        // Total Repayment: Rp5.150.000
        $result = $this->service->calculateLoan([
            'principal_amount'    => 5000000,
            'tenor_value'         => 30,
            'tenor_unit'          => 'days',
            'interest_rate'       => 0.1,
            'interest_period'     => 'daily',
            'interest_method'     => 'flat',
            'admin_fee_type'      => 'fixed',
            'admin_fee_value'     => 0,
            'repayment_frequency' => 'single',
        ]);

        $this->assertEquals(5000000.00, $result['principal_amount']);
        $this->assertEquals(150000.00, $result['interest_amount']);
        $this->assertEquals(0.00, $result['fee_amount']);
        $this->assertEquals(5150000.00, $result['total_amount']);
    }

    public function test_scenario_c_loan_with_monthly_interest_and_admin_fee(): void
    {
        // Scenario C – Pinjaman dengan bunga bulanan dan biaya admin
        // Principal: Rp10.000.000
        // Tenor: 6 Months
        // Interest Rate: 2% Monthly
        // Interest Method: Flat
        // Administration Fee: Rp100.000
        // Total Interest: Rp1.200.000
        // Total Repayment: Rp11.300.000
        $result = $this->service->calculateLoan([
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

        $this->assertEquals(10000000.00, $result['principal_amount']);
        $this->assertEquals(1200000.00, $result['interest_amount']);
        $this->assertEquals(100000.00, $result['fee_amount']);
        $this->assertEquals(11300000.00, $result['total_amount']);
    }

    public function test_effective_and_compound_interest_methods(): void
    {
        // Compound interest on 10.000.000 at 10% annual for 2 years
        // Total = 10.000.000 * (1.1)^2 = 12.100.000; Interest = 2.100.000
        $resultCompound = $this->service->calculateLoan([
            'principal_amount'    => 10000000,
            'tenor_value'         => 2,
            'tenor_unit'          => 'years',
            'interest_rate'       => 10,
            'interest_period'     => 'annual',
            'interest_method'     => 'compound',
            'repayment_frequency' => 'annually',
        ]);
        $this->assertEquals(2100000.00, $resultCompound['interest_amount']);
        $this->assertEquals(12100000.00, $resultCompound['total_amount']);

        // Effective interest annuity
        $resultEffective = $this->service->calculateLoan([
            'principal_amount'    => 12000000,
            'tenor_value'         => 12,
            'tenor_unit'          => 'months',
            'interest_rate'       => 12,
            'interest_period'     => 'annual',
            'interest_method'     => 'effective',
            'repayment_frequency' => 'monthly',
        ]);
        $this->assertGreaterThan(0, $resultEffective['interest_amount']);
        // With effective interest, total interest is less than flat interest (flat: 1.440.000)
        $this->assertLessThan(1440000.00, $resultEffective['interest_amount']);
    }

    public function test_generate_installments_reconciliation_and_rounding(): void
    {
        $user = User::factory()->create();

        // Create Debt: 10.000.000, 3 months, 2% monthly flat = 600.000 interest, 100.000 fee => 10.700.000 total
        $debt = Debt::create([
            'user_id'             => $user->id,
            'debt_code'           => 'DBT-TEST01',
            'creditor_name'       => 'Test Creditor',
            'debt_type'           => 'bank_loan',
            'direction'           => 'payable',
            'principal_amount'    => 10000000.00,
            'interest_rate'       => 2.00,
            'interest_period'     => 'monthly',
            'interest_method'     => 'flat',
            'fee_amount'          => 100000.00,
            'admin_fee_type'      => 'fixed',
            'admin_fee_value'     => 100000.00,
            'interest_amount'     => 600000.00,
            'total_amount'        => 10700000.00,
            'remaining_principal' => 10700000.00,
            'start_date'          => '2026-01-01',
            'due_date'            => '2026-04-01',
            'tenor_value'         => 3,
            'tenor_unit'          => 'months',
            'tenor_months'        => 3,
            'repayment_frequency' => 'monthly',
            'status'              => 'active',
        ]);

        $installments = $this->service->generateInstallments($debt);

        $this->assertCount(3, $installments);

        // Sum of all installments must match total_amount exactly
        $sumAmount = Installment::where('debt_id', $debt->id)->sum('amount');
        $this->assertEquals(10700000.00, (float) $sumAmount);

        // Sum of principal must match principal_amount exactly
        $sumPrincipal = Installment::where('debt_id', $debt->id)->sum('principal_amount');
        $this->assertEquals(10000000.00, (float) $sumPrincipal);

        // Sum of interest must match interest_amount exactly
        $sumInterest = Installment::where('debt_id', $debt->id)->sum('interest_amount');
        $this->assertEquals(600000.00, (float) $sumInterest);

        // Administration fee is charged ONLY ONCE (in 1st installment)
        $firstInst = Installment::where('debt_id', $debt->id)->where('installment_number', 1)->first();
        $this->assertEquals(100000.00, (float) $firstInst->fee_amount);

        $secondInst = Installment::where('debt_id', $debt->id)->where('installment_number', 2)->first();
        $this->assertEquals(0.00, (float) $secondInst->fee_amount);
    }

    public function test_single_lump_sum_short_tenor_installments(): void
    {
        $user = User::factory()->create();

        // 14 days loan, single lump sum at maturity
        $debt = Debt::create([
            'user_id'             => $user->id,
            'debt_code'           => 'DBT-SHORT01',
            'creditor_name'       => 'Pinjol Kilat',
            'debt_type'           => 'platform_online',
            'direction'           => 'payable',
            'principal_amount'    => 2000000.00,
            'interest_rate'       => 0.1,
            'interest_period'     => 'daily',
            'interest_method'     => 'flat',
            'fee_amount'          => 25000.00,
            'admin_fee_type'      => 'fixed',
            'admin_fee_value'     => 25000.00,
            'interest_amount'     => 28000.00, // 2.000.000 * 0.1% * 14 = 28.000
            'total_amount'        => 2053000.00,
            'remaining_principal' => 2053000.00,
            'start_date'          => '2026-05-01',
            'due_date'            => '2026-05-15',
            'tenor_value'         => 14,
            'tenor_unit'          => 'days',
            'tenor_months'        => 1,
            'repayment_frequency' => 'single',
            'status'              => 'active',
        ]);

        $installments = $this->service->generateInstallments($debt);

        $this->assertCount(1, $installments);
        $this->assertEquals(1, $installments[0]->installment_number);
        $this->assertEquals('2026-05-15', $installments[0]->due_date->toDateString());
        $this->assertEquals(2053000.00, (float) $installments[0]->amount);
        $this->assertEquals(2000000.00, (float) $installments[0]->principal_amount);
        $this->assertEquals(28000.00, (float) $installments[0]->interest_amount);
        $this->assertEquals(25000.00, (float) $installments[0]->fee_amount);
    }
}
