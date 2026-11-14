<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Debt;
use App\Models\Installment;
use App\Services\FinancialService;
use App\Services\LoanCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DebtController extends Controller
{
    protected FinancialService $financialService;
    protected LoanCalculationService $loanCalculationService;

    public function __construct(
        FinancialService $financialService,
        LoanCalculationService $loanCalculationService
    ) {
        $this->financialService = $financialService;
        $this->loanCalculationService = $loanCalculationService;
    }

    public function index(Request $request): View
    {
        $userId = $this->getUserId();

        $debts = Debt::where('user_id', $userId)
            ->with(['installments'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('direction'), fn($q) => $q->where('direction', $request->direction))
            ->orderBy('due_date', 'asc')
            ->get();

        $totalPayable = Debt::where('user_id', $userId)->where('direction', 'payable')->where('status', 'active')->sum('remaining_principal');
        $totalReceivable = Debt::where('user_id', $userId)->where('direction', 'receivable')->where('status', 'active')->sum('remaining_principal');
        $activeDebtsCount = Debt::where('user_id', $userId)->where('status', 'active')->count();

        // Upcoming installments
        $upcomingInstallments = Installment::whereHas('debt', fn($q) => $q->where('user_id', $userId))
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        $accounts = Account::where('user_id', $userId)->where('is_active', true)->get();

        return view('debts.index', compact(
            'debts',
            'totalPayable',
            'totalReceivable',
            'activeDebtsCount',
            'upcomingInstallments',
            'accounts'
        ));
    }

    public function create(): View
    {
        return view('debts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $this->getUserId();

        $validated = $request->validate([
            'creditor_name'       => 'required|string|max:150',
            'debt_type'           => 'required|in:platform_online,bank_loan,personal,credit_card,other',
            'direction'           => 'required|in:payable,receivable',
            'principal_amount'    => 'required|numeric|min:1',
            'tenor_value'         => 'nullable|integer|min:1',
            'tenor_unit'          => 'nullable|in:days,weeks,months,years',
            'tenor_months'        => 'nullable|integer|min:1',
            'repayment_frequency' => 'nullable|in:single,daily,weekly,monthly,annually',
            'interest_rate'       => 'nullable|numeric|min:0',
            'interest_period'     => 'nullable|in:daily,monthly,annual',
            'interest_method'     => 'nullable|in:flat,effective,compound',
            'admin_fee_type'      => 'nullable|in:fixed,percentage',
            'admin_fee_value'     => 'nullable|numeric|min:0',
            'fee_amount'          => 'nullable|numeric|min:0',
            'start_date'          => 'required|date',
            'due_date'            => 'required|date|after_or_equal:start_date',
            'notes'               => 'nullable|string',
        ]);

        // Compute full loan details using LoanCalculationService
        $calc = $this->loanCalculationService->calculateLoan($validated);

        $debt = Debt::create([
            'user_id'             => $userId,
            'creditor_name'       => $validated['creditor_name'],
            'debt_type'           => $validated['debt_type'],
            'direction'           => $validated['direction'],
            'principal_amount'    => $calc['principal_amount'],
            'interest_rate'       => $calc['interest_rate'],
            'interest_period'     => $calc['interest_period'],
            'interest_method'     => $calc['interest_method'],
            'fee_amount'          => $calc['fee_amount'],
            'admin_fee_type'      => $calc['admin_fee_type'],
            'admin_fee_value'     => $calc['admin_fee_value'],
            'interest_amount'     => $calc['interest_amount'],
            'total_amount'        => $calc['total_amount'],
            'remaining_principal' => $calc['total_amount'],
            'start_date'          => $calc['start_date'],
            'due_date'            => $calc['due_date'],
            'tenor_value'         => $calc['tenor_value'],
            'tenor_unit'          => $calc['tenor_unit'],
            'tenor_months'        => $calc['tenor_months'],
            'repayment_frequency' => $calc['repayment_frequency'],
            'status'              => 'active',
            'notes'               => $validated['notes'] ?? null,
        ]);

        // Generate schedule
        $this->financialService->generateInstallments($debt);

        AuditLog::record($userId, 'CREATE', 'debts', $debt->id, null, $debt->toArray());

        return redirect()->route('debts.index')->with('success', 'Debt recorded and installments schedule created.');
    }

    /**
     * Live calculation preview API for asynchronous preview requests.
     */
    public function calculatePreview(Request $request): JsonResponse
    {
        $calc = $this->loanCalculationService->calculateLoan($request->all());
        return response()->json($calc);
    }

    public function show(int $id): View
    {
        $userId = $this->getUserId();
        $debt = Debt::where('user_id', $userId)->with('installments.payments.account')->findOrFail($id);
        $accounts = Account::where('user_id', $userId)->where('is_active', true)->get();

        return view('debts.show', compact('debt', 'accounts'));
    }

    public function edit(int $id): View
    {
        $userId = $this->getUserId();
        $debt = Debt::where('user_id', $userId)->findOrFail($id);

        return view('debts.edit', compact('debt'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $userId = $this->getUserId();
        $debt = Debt::where('user_id', $userId)->findOrFail($id);

        $validated = $request->validate([
            'creditor_name' => 'required|string|max:150',
            'notes'         => 'nullable|string',
            'status'        => 'required|in:active,paid_off,defaulted,cancelled',
        ]);

        $oldValues = $debt->toArray();
        $debt->update($validated);

        AuditLog::record($userId, 'UPDATE', 'debts', $debt->id, $oldValues, $debt->fresh()->toArray());

        return redirect()->route('debts.index')->with('success', 'Debt updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $userId = $this->getUserId();
        $debt = Debt::where('user_id', $userId)->findOrFail($id);

        $oldValues = $debt->toArray();
        $debt->delete();

        AuditLog::record($userId, 'DELETE', 'debts', $debt->id, $oldValues, null);

        return redirect()->route('debts.index')->with('success', 'Debt deleted successfully.');
    }

    public function payInstallment(Request $request, int $debtId, int $installmentId): RedirectResponse
    {
        $userId = $this->getUserId();
        $debt = Debt::where('user_id', $userId)->findOrFail($debtId);
        $installment = Installment::where('debt_id', $debt->id)->findOrFail($installmentId);

        $validated = $request->validate([
            'account_id'  => 'required|exists:accounts,id',
            'amount_paid' => 'required|numeric|min:0.01',
            'notes'       => 'nullable|string|max:255',
        ]);

        $this->financialService->payInstallment(
            $installment,
            (int) $validated['account_id'],
            (float) $validated['amount_paid'],
            $validated['notes'] ?? null
        );

        return redirect()->route('debts.index')->with('success', 'Installment payment recorded successfully.');
    }
}
