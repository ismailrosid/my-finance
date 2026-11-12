<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Debt;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;

class FinancialService
{
    /**
     * Create a new transaction and update corresponding account balances according to financial logic.
     */
    public function createTransaction(array $data, int $userId): Transaction
    {
        return DB::transaction(function () use ($data, $userId) {
            $type     = $data['transaction_type'];
            $amount   = (float) ($data['amount'] ?? 0);
            $fee      = (float) ($data['fee_amount'] ?? 0);
            $expected = !empty($data['is_expected']);
            $status   = $data['transaction_status'] ?? 'completed';

            $transaction = Transaction::create([
                'user_id'                => $userId,
                'transaction_type'       => $type,
                'category_id'            => $data['category_id'] ?? null,
                'source_account_id'      => $data['source_account_id'] ?? null,
                'destination_account_id' => $data['destination_account_id'] ?? null,
                'amount'                 => $amount,
                'fee_amount'             => $fee,
                'transaction_date'       => $data['transaction_date'] ?? now()->toDateString(),
                'transaction_time'       => $data['transaction_time'] ?? now()->toTimeString(),
                'transaction_status'     => $status,
                'is_expected'            => $expected,
                'debt_id'                => $data['debt_id'] ?? null,
                'installment_id'         => $data['installment_id'] ?? null,
                'description'            => $data['description'] ?? '',
                'notes'                  => $data['notes'] ?? null,
                'attachment_url'         => $data['attachment_url'] ?? null,
            ]);

            // Save line items if provided
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (!empty($item['item_name'])) {
                        TransactionItem::create([
                            'transaction_id' => $transaction->id,
                            'item_name'      => $item['item_name'],
                            'quantity'       => $item['quantity'] ?? 1,
                            'unit_name'      => $item['unit_name'] ?? 'pcs',
                            'unit_price'     => $item['unit_price'] ?? 0,
                            'total_price'    => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
                            'category_id'    => $item['category_id'] ?? $transaction->category_id,
                            'notes'          => $item['notes'] ?? null,
                        ]);
                    }
                }
            }

            // Only update balances if actual (not expected) and completed
            if (!$expected && $status === 'completed') {
                $this->applyBalanceImpact($transaction);
            }

            // Record audit log
            AuditLog::record($userId, 'CREATE', 'transactions', $transaction->id, null, $transaction->toArray());

            return $transaction;
        });
    }

    /**
     * Apply financial balance impact based on transaction type.
     */
    protected function applyBalanceImpact(Transaction $transaction): void
    {
        $amount = (float) $transaction->amount;
        $fee    = (float) $transaction->fee_amount;

        switch ($transaction->transaction_type) {
            case 'income':
                if ($transaction->source_account_id) {
                    $account = Account::find($transaction->source_account_id);
                    if ($account) {
                        $account->current_balance += ($amount - $fee);
                        $account->save();
                    }
                }
                break;

            case 'expense':
                if ($transaction->source_account_id) {
                    $account = Account::find($transaction->source_account_id);
                    if ($account) {
                        $account->current_balance -= ($amount + $fee);
                        $account->save();
                    }
                }
                break;

            case 'transfer':
                // Deduct from source
                if ($transaction->source_account_id) {
                    $source = Account::find($transaction->source_account_id);
                    if ($source) {
                        $source->current_balance -= ($amount + $fee);
                        $source->save();
                    }
                }
                // Add to destination
                if ($transaction->destination_account_id) {
                    $destination = Account::find($transaction->destination_account_id);
                    if ($destination) {
                        $destination->current_balance += $amount;
                        $destination->save();
                    }
                }
                break;

            case 'debt_payment':
                // Deduct from paying account
                if ($transaction->source_account_id) {
                    $account = Account::find($transaction->source_account_id);
                    if ($account) {
                        $account->current_balance -= ($amount + $fee);
                        $account->save();
                    }
                }
                // Reduce debt remaining principal if linked
                if ($transaction->debt_id) {
                    $debt = Debt::find($transaction->debt_id);
                    if ($debt) {
                        $debt->remaining_principal = max(0, (float) $debt->remaining_principal - $amount);
                        if ($debt->remaining_principal <= 0) {
                            $debt->status = 'paid_off';
                        }
                        $debt->save();
                    }
                }
                break;
        }
    }

    /**
     * Reverse balance impact before deleting or updating.
     */
    protected function revertBalanceImpact(Transaction $transaction): void
    {
        if ($transaction->is_expected || $transaction->transaction_status !== 'completed') {
            return;
        }

        $amount = (float) $transaction->amount;
        $fee    = (float) $transaction->fee_amount;

        switch ($transaction->transaction_type) {
            case 'income':
                if ($transaction->source_account_id) {
                    $account = Account::find($transaction->source_account_id);
                    if ($account) {
                        $account->current_balance -= ($amount - $fee);
                        $account->save();
                    }
                }
                break;

            case 'expense':
                if ($transaction->source_account_id) {
                    $account = Account::find($transaction->source_account_id);
                    if ($account) {
                        $account->current_balance += ($amount + $fee);
                        $account->save();
                    }
                }
                break;

            case 'transfer':
                if ($transaction->source_account_id) {
                    $source = Account::find($transaction->source_account_id);
                    if ($source) {
                        $source->current_balance += ($amount + $fee);
                        $source->save();
                    }
                }
                if ($transaction->destination_account_id) {
                    $destination = Account::find($transaction->destination_account_id);
                    if ($destination) {
                        $destination->current_balance -= $amount;
                        $destination->save();
                    }
                }
                break;

            case 'debt_payment':
                if ($transaction->source_account_id) {
                    $account = Account::find($transaction->source_account_id);
                    if ($account) {
                        $account->current_balance += ($amount + $fee);
                        $account->save();
                    }
                }
                if ($transaction->debt_id) {
                    $debt = Debt::find($transaction->debt_id);
                    if ($debt) {
                        $debt->remaining_principal = min((float) $debt->total_amount, (float) $debt->remaining_principal + $amount);
                        if ($debt->status === 'paid_off' && $debt->remaining_principal > 0) {
                            $debt->status = 'active';
                        }
                        $debt->save();
                    }
                }
                break;
        }
    }

    /**
     * Delete a transaction and reverse balance impacts.
     */
    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $this->revertBalanceImpact($transaction);
            $oldValues = $transaction->toArray();
            $transaction->delete();
            AuditLog::record($transaction->user_id, 'DELETE', 'transactions', $transaction->id, $oldValues, null);
        });
    }

    /**
     * Pay an installment.
     */
    public function payInstallment(Installment $installment, int $accountId, float $amount, ?string $notes = null): InstallmentPayment
    {
        return DB::transaction(function () use ($installment, $accountId, $amount, $notes) {
            $debt = $installment->debt;
            $user = $debt->user;

            // 1. Create debt payment transaction
            $transaction = $this->createTransaction([
                'transaction_type'   => 'debt_payment',
                'source_account_id'  => $accountId,
                'amount'             => $amount,
                'fee_amount'         => 0,
                'transaction_date'   => now()->toDateString(),
                'transaction_time'   => now()->toTimeString(),
                'transaction_status' => 'completed',
                'is_expected'        => false,
                'debt_id'            => $debt->id,
                'installment_id'     => $installment->id,
                'description'        => "Installment #{$installment->installment_number} payment for {$debt->creditor_name}",
                'notes'              => $notes,
            ], $debt->user_id);

            // 2. Update installment
            $installment->paid_amount = (float) $installment->paid_amount + $amount;
            $installment->payment_date = now()->toDateString();
            if ($installment->paid_amount >= (float) $installment->amount) {
                $installment->status = 'paid';
            } else {
                $installment->status = 'partially_paid';
            }
            $installment->save();

            // 3. Create installment payment record
            $payment = InstallmentPayment::create([
                'installment_id' => $installment->id,
                'transaction_id' => $transaction->id,
                'account_id'     => $accountId,
                'payment_date'   => now()->toDateString(),
                'amount_paid'    => $amount,
                'principal_paid' => min($amount, (float) $installment->principal_amount),
                'interest_paid'  => max(0, $amount - (float) $installment->principal_amount),
                'notes'          => $notes,
            ]);

            return $payment;
        });
    }

    /**
     * Create installments schedule for a debt.
     */
    public function generateInstallments(Debt $debt): void
    {
        $tenor = max(1, (int) $debt->tenor_months);
        $totalAmount = (float) $debt->total_amount;
        $principalPerMonth = round((float) $debt->principal_amount / $tenor, 2);
        $totalPerMonth = round($totalAmount / $tenor, 2);
        $interestPerMonth = max(0, round(($totalAmount - (float) $debt->principal_amount) / $tenor, 2));

        $startDate = \Carbon\Carbon::parse($debt->start_date);

        for ($i = 1; $i <= $tenor; $i++) {
            $dueDate = $startDate->copy()->addMonths($i);

            // Adjust last month rounding differences
            $amount = ($i === $tenor) ? ($totalAmount - ($totalPerMonth * ($tenor - 1))) : $totalPerMonth;
            $principal = ($i === $tenor) ? ((float) $debt->principal_amount - ($principalPerMonth * ($tenor - 1))) : $principalPerMonth;
            $interest = max(0, $amount - $principal);

            Installment::create([
                'debt_id'            => $debt->id,
                'installment_number' => $i,
                'due_date'           => $dueDate->toDateString(),
                'amount'             => $amount,
                'principal_amount'   => $principal,
                'interest_amount'    => $interest,
                'fee_amount'         => 0,
                'paid_amount'        => 0,
                'status'             => 'unpaid',
            ]);
        }
    }
}

