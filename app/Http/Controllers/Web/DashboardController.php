<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Installment;
use App\Models\Transaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = $this->getUserId();
        $now = now();

        // 1. Total Balance across all active accounts
        $totalBalance = Account::where('user_id', $userId)
            ->where('is_active', true)
            ->sum('current_balance');

        // 2. Income this month (actual & completed)
        $incomeThisMonth = Transaction::where('user_id', $userId)
            ->where('transaction_type', 'income')
            ->where('is_expected', false)
            ->where('transaction_status', 'completed')
            ->whereMonth('transaction_date', $now->month)
            ->whereYear('transaction_date', $now->year)
            ->sum('amount');

        // 3. Expenses this month (actual & completed)
        $expensesThisMonth = Transaction::where('user_id', $userId)
            ->where('transaction_type', 'expense')
            ->where('is_expected', false)
            ->where('transaction_status', 'completed')
            ->whereMonth('transaction_date', $now->month)
            ->whereYear('transaction_date', $now->year)
            ->sum('amount');

        // 4. Net Cash Flow
        $netCashFlow = $incomeThisMonth - $expensesThisMonth;

        // 5. Recent 5 Transactions
        $recentTransactions = Transaction::where('user_id', $userId)
            ->with(['category', 'sourceAccount', 'destinationAccount'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // 6. Active Accounts
        $accounts = Account::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('current_balance', 'desc')
            ->take(4)
            ->get();

        // 7. Budgets for current month
        $budgets = Budget::where('user_id', $userId)
            ->where('period_month', $now->month)
            ->where('period_year', $now->year)
            ->with('category')
            ->take(4)
            ->get();

        // 8. Upcoming Installments
        $upcomingInstallments = Installment::whereHas('debt', fn($q) => $q->where('user_id', $userId))
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->with('debt')
            ->orderBy('due_date', 'asc')
            ->take(3)
            ->get();

        return view('dashboard.index', compact(
            'totalBalance',
            'incomeThisMonth',
            'expensesThisMonth',
            'netCashFlow',
            'recentTransactions',
            'accounts',
            'budgets',
            'upcomingInstallments'
        ));
    }
}
