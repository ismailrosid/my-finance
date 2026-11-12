<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $this->getUserId();

        $year = (int) ($request->input('year', now()->year));
        $month = (int) ($request->input('month', now()->month));

        // 1. Actual Income
        $actualIncome = Transaction::where('user_id', $userId)
            ->where('transaction_type', 'income')
            ->where('is_expected', false)
            ->where('transaction_status', 'completed')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->sum('amount');

        // 2. Expected Income
        $expectedIncome = Transaction::where('user_id', $userId)
            ->where('transaction_type', 'income')
            ->where('is_expected', true)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->sum('amount');

        // 3. Actual Expense
        $actualExpense = Transaction::where('user_id', $userId)
            ->where('transaction_type', 'expense')
            ->where('is_expected', false)
            ->where('transaction_status', 'completed')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->sum('amount');

        // 4. Debt Payments (excluded from expense)
        $debtPayments = Transaction::where('user_id', $userId)
            ->where('transaction_type', 'debt_payment')
            ->where('is_expected', false)
            ->where('transaction_status', 'completed')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->sum('amount');

        // 5. Actual Cashflow = Actual Income - Actual Expense - Debt Payments
        $actualCashflow = $actualIncome - $actualExpense - $debtPayments;

        // 6. Projected Cashflow = (Actual + Expected Income) - Actual Expense - Debt Payments
        $projectedCashflow = ($actualIncome + $expectedIncome) - $actualExpense - $debtPayments;

        // 7. Outstanding Debt
        $outstandingDebt = Debt::where('user_id', $userId)
            ->where('direction', 'payable')
            ->where('status', 'active')
            ->sum('remaining_principal');

        // 8. Expense breakdown by category
        $expenseCategories = Transaction::where('user_id', $userId)
            ->where('transaction_type', 'expense')
            ->where('is_expected', false)
            ->where('transaction_status', 'completed')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        return view('reports.index', compact(
            'year',
            'month',
            'actualIncome',
            'expectedIncome',
            'actualExpense',
            'debtPayments',
            'actualCashflow',
            'projectedCashflow',
            'outstandingDebt',
            'expenseCategories'
        ));
    }

    public function cashflow(Request $request): View
    {
        return $this->index($request);
    }

    public function export(Request $request): StreamedResponse
    {
        $userId = $this->getUserId();
        $year = (int) ($request->input('year', now()->year));
        $month = (int) ($request->input('month', now()->month));

        $transactions = Transaction::where('user_id', $userId)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->with(['category', 'sourceAccount', 'destinationAccount'])
            ->orderBy('transaction_date', 'asc')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"financial-report-{$year}-{$month}.csv\"",
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Code', 'Type', 'Category', 'Description', 'Amount', 'Fee', 'Source Account', 'Status', 'Expected']);

            foreach ($transactions as $txn) {
                fputcsv($handle, [
                    $txn->transaction_date->format('Y-m-d'),
                    $txn->transaction_code,
                    $txn->transaction_type,
                    $txn->category?->category_name ?? '-',
                    $txn->description,
                    $txn->amount,
                    $txn->fee_amount,
                    $txn->sourceAccount?->account_name ?? '-',
                    $txn->transaction_status,
                    $txn->is_expected ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
