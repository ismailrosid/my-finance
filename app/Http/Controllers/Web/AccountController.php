<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $this->getUserId();

        $accounts = Account::where('user_id', $userId)
            ->when($request->filled('type'), fn($q) => $q->where('account_type', $request->type))
            ->when($request->filled('search'), fn($q) => $q->where('account_name', 'like', "%{$request->search}%"))
            ->orderBy('created_at', 'desc')
            ->get();

        $totalBalance = Account::where('user_id', $userId)->where('is_active', true)->sum('current_balance');
        $bankBalance = Account::where('user_id', $userId)->where('is_active', true)->where('account_type', 'bank')->sum('current_balance');
        $walletBalance = Account::where('user_id', $userId)->where('is_active', true)->where('account_type', 'e_wallet')->sum('current_balance');
        $cashBalance = Account::where('user_id', $userId)->where('is_active', true)->where('account_type', 'cash')->sum('current_balance');

        return view('accounts.index', compact(
            'accounts',
            'totalBalance',
            'bankBalance',
            'walletBalance',
            'cashBalance'
        ));
    }

    public function create(): View
    {
        return view('accounts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $this->getUserId();

        $validated = $request->validate([
            'account_name'    => 'required|string|max:100',
            'account_type'    => 'required|in:bank,e_wallet,cash,credit_card,investment,other',
            'account_number'  => 'nullable|string|max:50',
            'bank_name'       => 'nullable|string|max:100',
            'initial_balance' => 'required|numeric|min:0',
            'color'           => 'nullable|string|max:20',
            'notes'           => 'nullable|string|max:255',
        ]);

        $account = Account::create([
            'user_id'         => $userId,
            'account_name'    => $validated['account_name'],
            'account_type'    => $validated['account_type'],
            'account_number'  => $validated['account_number'] ?? null,
            'bank_name'       => $validated['bank_name'] ?? null,
            'currency_code'   => 'IDR',
            'initial_balance' => $validated['initial_balance'],
            'current_balance' => $validated['initial_balance'],
            'color'           => $validated['color'] ?? '#10B981',
            'is_active'       => true,
            'notes'           => $validated['notes'] ?? null,
        ]);

        AuditLog::record($userId, 'CREATE', 'accounts', $account->id, null, $account->toArray());

        return redirect()->route('accounts.index')->with('success', 'Account created successfully.');
    }

    public function show(int $id): View
    {
        $userId = $this->getUserId();
        $account = Account::where('user_id', $userId)->findOrFail($id);

        $transactions = $account->outgoingTransactions()
            ->orWhere('destination_account_id', $account->id)
            ->where('user_id', $userId)
            ->orderBy('transaction_date', 'desc')
            ->take(20)
            ->get();

        return view('accounts.show', compact('account', 'transactions'));
    }

    public function edit(int $id): View
    {
        $userId = $this->getUserId();
        $account = Account::where('user_id', $userId)->findOrFail($id);

        return view('accounts.edit', compact('account'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $userId = $this->getUserId();
        $account = Account::where('user_id', $userId)->findOrFail($id);

        $validated = $request->validate([
            'account_name'   => 'required|string|max:100',
            'account_type'   => 'required|in:bank,e_wallet,cash,credit_card,investment,other',
            'account_number' => 'nullable|string|max:50',
            'bank_name'      => 'nullable|string|max:100',
            'color'          => 'nullable|string|max:20',
            'is_active'      => 'nullable|boolean',
            'notes'          => 'nullable|string|max:255',
        ]);

        $oldValues = $account->toArray();

        $account->update([
            'account_name'   => $validated['account_name'],
            'account_type'   => $validated['account_type'],
            'account_number' => $validated['account_number'] ?? null,
            'bank_name'      => $validated['bank_name'] ?? null,
            'color'          => $validated['color'] ?? $account->color,
            'is_active'      => $request->has('is_active'),
            'notes'          => $validated['notes'] ?? null,
        ]);

        AuditLog::record($userId, 'UPDATE', 'accounts', $account->id, $oldValues, $account->toArray());

        return redirect()->route('accounts.index')->with('success', 'Account updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $userId = $this->getUserId();
        $account = Account::where('user_id', $userId)->findOrFail($id);

        $oldValues = $account->toArray();
        $account->delete();

        AuditLog::record($userId, 'DELETE', 'accounts', $account->id, $oldValues, null);

        return redirect()->route('accounts.index')->with('success', 'Account deleted successfully.');
    }
}
