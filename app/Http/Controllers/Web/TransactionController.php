<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Transaction;
use App\Services\FinancialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    protected FinancialService $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function index(Request $request): View
    {
        $userId = $this->getUserId();

        $query = Transaction::where('user_id', $userId)
            ->with(['category', 'sourceAccount', 'destinationAccount'])
            ->when($request->filled('type'), fn($q) => $q->where('transaction_type', $request->type))
            ->when($request->filled('account_id'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('source_account_id', $request->account_id)
                        ->orWhere('destination_account_id', $request->account_id);
                });
            })
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('description', 'like', "%{$request->search}%")
                        ->orWhere('transaction_code', 'like', "%{$request->search}%");
                });
            })
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('transaction_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('transaction_date', '<=', $request->date_to))
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        $transactions = $query->paginate(15)->withQueryString();

        // Metrics for current month or selected filter
        $totalIncome = Transaction::where('user_id', $userId)
            ->where('transaction_type', 'income')
            ->where('is_expected', false)
            ->where('transaction_status', 'completed')
            ->sum('amount');

        $totalExpense = Transaction::where('user_id', $userId)
            ->where('transaction_type', 'expense')
            ->where('is_expected', false)
            ->where('transaction_status', 'completed')
            ->sum('amount');

        $netBalance = $totalIncome - $totalExpense;

        $accounts = Account::where('user_id', $userId)->where('is_active', true)->get();
        $categories = Category::forUser($userId)->where('is_active', true)->get();

        return view('transactions.index', compact(
            'transactions',
            'totalIncome',
            'totalExpense',
            'netBalance',
            'accounts',
            'categories'
        ));
    }

    public function create(): View
    {
        $userId = $this->getUserId();
        $accounts = Account::where('user_id', $userId)->where('is_active', true)->get();
        $categories = Category::forUser($userId)->where('is_active', true)->get();
        $debts = Debt::where('user_id', $userId)->where('status', 'active')->get();

        return view('transactions.create', compact('accounts', 'categories', 'debts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $this->getUserId();

        $validated = $request->validate([
            'transaction_type'       => 'required|in:income,expense,transfer,debt_payment',
            'amount'                 => 'required|numeric|min:0.01',
            'fee_amount'             => 'nullable|numeric|min:0',
            'transaction_date'       => 'required|date',
            'category_id'            => 'nullable|exists:categories,id',
            'source_account_id'      => 'nullable|exists:accounts,id',
            'destination_account_id' => 'nullable|exists:accounts,id|different:source_account_id',
            'debt_id'                => 'nullable|exists:debts,id',
            'description'            => 'required|string|max:255',
            'notes'                  => 'nullable|string',
            'is_expected'            => 'nullable|boolean',
        ]);

        $validated['is_expected'] = $request->boolean('is_expected');
        $validated['fee_amount'] = $validated['fee_amount'] ?? 0;

        $this->financialService->createTransaction($validated, $userId);

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    public function edit(int $id): View
    {
        $userId = $this->getUserId();
        $transaction = Transaction::where('user_id', $userId)->findOrFail($id);
        $accounts = Account::where('user_id', $userId)->where('is_active', true)->get();
        $categories = Category::forUser($userId)->where('is_active', true)->get();

        return view('transactions.edit', compact('transaction', 'accounts', 'categories'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $userId = $this->getUserId();
        $transaction = Transaction::where('user_id', $userId)->findOrFail($id);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'notes'       => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $transaction->update($validated);

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $userId = $this->getUserId();
        $transaction = Transaction::where('user_id', $userId)->findOrFail($id);

        $this->financialService->deleteTransaction($transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaction deleted and balance reverted.');
    }
}
