<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LoanCalculationService
{
    /**
     * Calculate final due date from start date and flexible tenor.
     */
    public function calculateDueDate(Carbon|string $startDate, int $tenorValue, string $tenorUnit): Carbon
    {
        $start = $startDate instanceof Carbon ? $startDate->copy() : Carbon::parse($startDate);
        $val = max(1, $tenorValue);

        return match (strtolower($tenorUnit)) {
            'days', 'day'     => $start->addDays($val),
            'weeks', 'week'   => $start->addWeeks($val),
            'years', 'year'   => $start->addYearsNoOverflow($val),
            default           => $start->addMonthsNoOverflow($val), // 'months'
        };
    }

    /**
     * Calculate one-time administration fee in rupiah based on fee type and value.
     */
    public function calculateAdminFee(float $principal, string $feeType, float $feeValue): float
    {
        if ($feeValue <= 0 || $principal <= 0) {
            return 0.00;
        }

        if (strtolower($feeType) === 'percentage') {
            return round($principal * ($feeValue / 100), 2);
        }

        // Fixed amount
        return round($feeValue, 2);
    }

    /**
     * Calculate total interest over the loan tenor.
     */
    public function calculateInterest(
        float $principal,
        float $interestRate,
        string $interestPeriod,
        string $interestMethod,
        int $tenorValue,
        string $tenorUnit,
        Carbon|string|null $startDate = null,
        Carbon|string|null $dueDate = null,
        int $installmentsCount = 1
    ): float {
        if ($interestRate <= 0 || $principal <= 0) {
            return 0.00;
        }

        $period = strtolower($interestPeriod);
        $method = strtolower($interestMethod);
        $unit   = strtolower($tenorUnit);
        $tenor  = max(1, $tenorValue);

        // Convert duration to unit of interest period
        $duration = $this->getDurationInInterestPeriod($tenor, $unit, $period, $startDate, $dueDate);

        switch ($method) {
            case 'compound':
                // A = P * (1 + r)^t - P
                $rateDecimal = $interestRate / 100;
                $totalAmount = $principal * pow(1 + $rateDecimal, $duration);
                return max(0.00, round($totalAmount - $principal, 2));

            case 'effective':
                // Amortization (Annuity) based on installments
                $n = max(1, $installmentsCount);
                if ($n === 1) {
                    return round($principal * ($interestRate / 100) * $duration, 2);
                }

                // Periodic rate per installment
                $periodicRate = ($interestRate / 100) * ($duration / $n);
                if ($periodicRate <= 0) {
                    return 0.00;
                }

                // PMT = P * [i(1+i)^n] / [(1+i)^n - 1]
                $factor = pow(1 + $periodicRate, $n);
                $pmt = $principal * ($periodicRate * $factor) / ($factor - 1);

                $balance = $principal;
                $totalInterest = 0.0;
                for ($k = 1; $k <= $n; $k++) {
                    $interest = round($balance * $periodicRate, 2);
                    $principalPaid = ($k === $n) ? $balance : round($pmt - $interest, 2);
                    $balance = max(0, $balance - $principalPaid);
                    $totalInterest += $interest;
                }
                return round($totalInterest, 2);

            case 'flat':
            default:
                // Simple / Flat interest: P * r * duration
                $rateDecimal = $interestRate / 100;
                return round($principal * $rateDecimal * $duration, 2);
        }
    }

    /**
     * Compute duration expressed in the interest period units.
     */
    protected function getDurationInInterestPeriod(
        int $tenorValue,
        string $tenorUnit,
        string $interestPeriod,
        Carbon|string|null $startDate = null,
        Carbon|string|null $dueDate = null
    ): float {
        // Daily interest
        if ($interestPeriod === 'daily') {
            return match ($tenorUnit) {
                'days'   => (float) $tenorValue,
                'weeks'  => (float) ($tenorValue * 7),
                'years'  => (float) ($tenorValue * 365),
                default  => ($startDate && $dueDate)
                    ? (float) Carbon::parse($startDate)->diffInDays(Carbon::parse($dueDate))
                    : (float) ($tenorValue * 30),
            };
        }

        // Monthly interest
        if ($interestPeriod === 'monthly') {
            return match ($tenorUnit) {
                'months' => (float) $tenorValue,
                'years'  => (float) ($tenorValue * 12),
                'weeks'  => ($tenorValue * 7) / 30,
                default  => ($tenorValue / 30), // days
            };
        }

        // Annual interest (default)
        return match ($tenorUnit) {
            'years'  => (float) $tenorValue,
            'months' => $tenorValue / 12,
            'weeks'  => ($tenorValue * 7) / 365,
            default  => $tenorValue / 365, // days
        };
    }

    /**
     * Determine number of installments based on tenor and repayment frequency.
     */
    public function resolveInstallmentsCount(int $tenorValue, string $tenorUnit, string $repaymentFrequency): int
    {
        $freq = strtolower($repaymentFrequency);
        $unit = strtolower($tenorUnit);
        $val  = max(1, $tenorValue);

        if ($freq === 'single') {
            return 1;
        }

        if ($freq === 'daily') {
            return match ($unit) {
                'days'   => $val,
                'weeks'  => $val * 7,
                'months' => $val * 30,
                'years'  => $val * 365,
            };
        }

        if ($freq === 'weekly') {
            return match ($unit) {
                'weeks'  => $val,
                'days'   => max(1, (int) ceil($val / 7)),
                'months' => max(1, (int) round(($val * 30) / 7)),
                'years'  => max(1, $val * 52),
            };
        }

        if ($freq === 'annually') {
            return match ($unit) {
                'years'  => $val,
                default  => 1,
            };
        }

        // Monthly (default)
        return match ($unit) {
            'months' => $val,
            'years'  => $val * 12,
            'weeks'  => max(1, (int) round(($val * 7) / 30)),
            'days'   => max(1, (int) round($val / 30)),
        };
    }

    /**
     * Compute full loan breakdown from parameters.
     */
    public function calculateLoan(array $params): array
    {
        $principal = (float) ($params['principal_amount'] ?? 0);
        $tenorValue = (int) ($params['tenor_value'] ?? ($params['tenor_months'] ?? 1));
        if ($tenorValue < 1) {
            $tenorValue = 1;
        }

        $tenorUnit = strtolower($params['tenor_unit'] ?? 'months');
        if (!in_array($tenorUnit, ['days', 'weeks', 'months', 'years'])) {
            $tenorUnit = 'months';
        }

        $startDate = isset($params['start_date']) ? Carbon::parse($params['start_date']) : now();

        // Calculate or respect provided due_date
        $autoDueDate = $this->calculateDueDate($startDate, $tenorValue, $tenorUnit);
        $dueDate = !empty($params['due_date']) ? Carbon::parse($params['due_date']) : $autoDueDate;

        // Repayment Frequency
        $repaymentFreq = strtolower($params['repayment_frequency'] ?? '');
        if (empty($repaymentFreq) || !in_array($repaymentFreq, ['single', 'daily', 'weekly', 'monthly', 'annually'])) {
            $repaymentFreq = ($tenorUnit === 'days' && $tenorValue <= 31) ? 'single' : 'monthly';
        }

        $installmentsCount = $this->resolveInstallmentsCount($tenorValue, $tenorUnit, $repaymentFreq);

        // Interest
        $interestRate = max(0.0, (float) ($params['interest_rate'] ?? 0));
        $interestPeriod = strtolower($params['interest_period'] ?? 'annual');
        if (!in_array($interestPeriod, ['daily', 'monthly', 'annual'])) {
            $interestPeriod = 'annual';
        }

        $interestMethod = strtolower($params['interest_method'] ?? 'flat');
        if (!in_array($interestMethod, ['flat', 'effective', 'compound'])) {
            $interestMethod = 'flat';
        }

        $totalInterest = $this->calculateInterest(
            $principal,
            $interestRate,
            $interestPeriod,
            $interestMethod,
            $tenorValue,
            $tenorUnit,
            $startDate,
            $dueDate,
            $installmentsCount
        );

        // Administration Fee
        $adminFeeType = strtolower($params['admin_fee_type'] ?? 'fixed');
        if (!in_array($adminFeeType, ['fixed', 'percentage'])) {
            $adminFeeType = 'fixed';
        }
        $adminFeeValue = max(0.0, (float) ($params['admin_fee_value'] ?? ($params['fee_amount'] ?? 0)));
        $feeAmount = $this->calculateAdminFee($principal, $adminFeeType, $adminFeeValue);

        // Other Fees
        $otherFees = max(0.0, (float) ($params['other_fees'] ?? 0));

        // Total Repayment = Principal Amount + Total Interest + Administration Fee + Other Fees
        $totalRepayment = round($principal + $totalInterest + $feeAmount + $otherFees, 2);

        // Tenor in months equivalent for backward compatibility
        $tenorMonths = match ($tenorUnit) {
            'months' => $tenorValue,
            'years'  => $tenorValue * 12,
            'weeks'  => max(1, (int) round(($tenorValue * 7) / 30)),
            'days'   => max(1, (int) round($tenorValue / 30)),
        };

        $estimatedInstallmentAmount = $installmentsCount > 0 ? round($totalRepayment / $installmentsCount, 2) : $totalRepayment;

        return [
            'principal_amount'           => $principal,
            'tenor_value'                => $tenorValue,
            'tenor_unit'                 => $tenorUnit,
            'tenor_months'               => $tenorMonths,
            'start_date'                 => $startDate->toDateString(),
            'due_date'                   => $dueDate->toDateString(),
            'repayment_frequency'        => $repaymentFreq,
            'installments_count'         => $installmentsCount,
            'interest_rate'              => $interestRate,
            'interest_period'            => $interestPeriod,
            'interest_method'            => $interestMethod,
            'admin_fee_type'             => $adminFeeType,
            'admin_fee_value'            => $adminFeeValue,
            'fee_amount'                 => $feeAmount,
            'interest_amount'            => $totalInterest,
            'other_fees'                 => $otherFees,
            'total_amount'               => $totalRepayment,
            'estimated_installment_amount' => $estimatedInstallmentAmount,
        ];
    }

    /**
     * Generate installment schedule for a Debt instance.
     */
    public function generateInstallments(Debt $debt): array
    {
        // Delete any existing unpaid installments to allow regeneration if needed
        Installment::where('debt_id', $debt->id)->where('status', 'unpaid')->delete();

        $tenorValue = $debt->tenor_value ?: ($debt->tenor_months ?: 1);
        $tenorUnit = $debt->tenor_unit ?: 'months';
        $repaymentFreq = $debt->repayment_frequency ?: (($tenorUnit === 'days' && $tenorValue <= 31) ? 'single' : 'monthly');
        $n = $this->resolveInstallmentsCount($tenorValue, $tenorUnit, $repaymentFreq);

        $startDate = Carbon::parse($debt->start_date);
        $finalDueDate = Carbon::parse($debt->due_date);

        $principalTotal = (float) $debt->principal_amount;
        $interestTotal  = (float) $debt->interest_amount;
        $adminFeeTotal  = (float) $debt->fee_amount;
        $totalRepayment = (float) $debt->total_amount;

        $installments = [];

        // Scenario 1: Single payment / Lump sum at maturity
        if ($n === 1 || $repaymentFreq === 'single') {
            $installment = Installment::create([
                'debt_id'            => $debt->id,
                'installment_number' => 1,
                'due_date'           => $finalDueDate->toDateString(),
                'amount'             => $totalRepayment,
                'principal_amount'   => $principalTotal,
                'interest_amount'    => $interestTotal,
                'fee_amount'         => $adminFeeTotal,
                'paid_amount'        => 0.00,
                'status'             => 'unpaid',
            ]);
            $installments[] = $installment;
            return $installments;
        }

        // Scenario 2: Multiple installments
        $method = strtolower($debt->interest_method ?: 'flat');

        // Effective method schedule calculation
        if ($method === 'effective' && $interestTotal > 0) {
            $duration = $this->getDurationInInterestPeriod($tenorValue, $tenorUnit, $debt->interest_period ?: 'annual', $startDate, $finalDueDate);
            $periodicRate = ($debt->interest_rate / 100) * ($duration / $n);
            $factor = pow(1 + $periodicRate, $n);
            $pmt = $principalTotal * ($periodicRate * $factor) / ($factor - 1);

            $balance = $principalTotal;
            $accumulatedPrincipal = 0.0;
            $accumulatedInterest = 0.0;

            for ($i = 1; $i <= $n; $i++) {
                $dueDate = $this->getInstallmentDueDate($startDate, $finalDueDate, $i, $n, $repaymentFreq);

                $interest = round($balance * $periodicRate, 2);
                if ($i === $n) {
                    $principal = round($principalTotal - $accumulatedPrincipal, 2);
                    $interest = round($interestTotal - $accumulatedInterest, 2);
                } else {
                    $principal = round($pmt - $interest, 2);
                    $balance = max(0, $balance - $principal);
                }

                $accumulatedPrincipal += $principal;
                $accumulatedInterest += $interest;

                // Administration fee is charged ONCE in installment #1
                $fee = ($i === 1) ? $adminFeeTotal : 0.00;
                $amount = round($principal + $interest + $fee, 2);

                $installments[] = Installment::create([
                    'debt_id'            => $debt->id,
                    'installment_number' => $i,
                    'due_date'           => $dueDate->toDateString(),
                    'amount'             => $amount,
                    'principal_amount'   => $principal,
                    'interest_amount'    => $interest,
                    'fee_amount'         => $fee,
                    'paid_amount'        => 0.00,
                    'status'             => 'unpaid',
                ]);
            }

            return $installments;
        }

        // Flat or Compound: equal principal and equal interest across installments
        $principalPerInst = round($principalTotal / $n, 2);
        $interestPerInst  = round($interestTotal / $n, 2);

        $runningPrincipal = 0.0;
        $runningInterest  = 0.0;

        for ($i = 1; $i <= $n; $i++) {
            $dueDate = $this->getInstallmentDueDate($startDate, $finalDueDate, $i, $n, $repaymentFreq);

            // Last installment absorbs any rounding differences
            if ($i === $n) {
                $principal = round($principalTotal - $runningPrincipal, 2);
                $interest  = round($interestTotal - $runningInterest, 2);
            } else {
                $principal = $principalPerInst;
                $interest  = $interestPerInst;
                $runningPrincipal += $principal;
                $runningInterest += $interest;
            }

            // Administration fee is charged ONCE in installment #1
            $fee = ($i === 1) ? $adminFeeTotal : 0.00;
            $amount = round($principal + $interest + $fee, 2);

            $installments[] = Installment::create([
                'debt_id'            => $debt->id,
                'installment_number' => $i,
                'due_date'           => $dueDate->toDateString(),
                'amount'             => $amount,
                'principal_amount'   => $principal,
                'interest_amount'    => $interest,
                'fee_amount'         => $fee,
                'paid_amount'        => 0.00,
                'status'             => 'unpaid',
            ]);
        }

        return $installments;
    }

    /**
     * Compute the due date for installment $i.
     */
    protected function getInstallmentDueDate(Carbon $startDate, Carbon $finalDueDate, int $i, int $total, string $frequency): Carbon
    {
        if ($i === $total) {
            return $finalDueDate;
        }

        $date = match (strtolower($frequency)) {
            'daily'    => $startDate->copy()->addDays($i),
            'weekly'   => $startDate->copy()->addWeeks($i),
            'annually' => $startDate->copy()->addYearsNoOverflow($i),
            default    => $startDate->copy()->addMonthsNoOverflow($i), // monthly
        };

        // Don't let periodic due date exceed final due date
        return $date->gt($finalDueDate) ? $finalDueDate : $date;
    }
}
