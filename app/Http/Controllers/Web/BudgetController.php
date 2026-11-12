<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $this->getUserId();

        $month = (int) ($request->input('month', now()->month));
        $year  = (int) ($request->input('year', now()->year));

        $budgets = Budget::where('user_id', $userId)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->with('category')
            ->get();

        $totalBudget = $budgets->sum('amount_limit');
        $totalSpent = $budgets->sum(fn($b) => $b->spent_amount);
        $totalRemaining = max(0, $totalBudget - $totalSpent);

        $categories = Category::forUser($userId)
            ->where('category_type', 'expense')
            ->where('is_active', true)
            ->get();

        return view('budgets.index', compact(
            'budgets',
            'month',
            'year',
            'totalBudget',
            'totalSpent',
            'totalRemaining',
            'categories'
        ));
    }

    public function create(): View
    {
        $userId = $this->getUserId();
        $categories = Category::forUser($userId)->where('category_type', 'expense')->where('is_active', true)->get();

        return view('budgets.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $this->getUserId();

        $validated = $request->validate([
            'category_id'                => 'required|exists:categories,id',
            'period_month'               => 'required|integer|between:1,12',
            'period_year'                => 'required|integer|min:2020|max:2030',
            'amount_limit'               => 'required|numeric|min:1',
            'alert_threshold_percentage' => 'nullable|integer|between:1,100',
            'notes'                      => 'nullable|string|max:255',
        ]);

        $budget = Budget::updateOrCreate(
            [
                'user_id'      => $userId,
                'category_id'  => $validated['category_id'],
                'period_month' => $validated['period_month'],
                'period_year'  => $validated['period_year'],
            ],
            [
                'amount_limit'               => $validated['amount_limit'],
                'alert_threshold_percentage' => $validated['alert_threshold_percentage'] ?? 80,
                'notes'                      => $validated['notes'] ?? null,
            ]
        );

        AuditLog::record($userId, 'CREATE', 'budgets', $budget->id, null, $budget->toArray());

        return redirect()->route('budgets.index', [
            'month' => $validated['period_month'],
            'year'  => $validated['period_year'],
        ])->with('success', 'Budget saved successfully.');
    }

    public function edit(int $id): View
    {
        $userId = $this->getUserId();
        $budget = Budget::where('user_id', $userId)->with('category')->findOrFail($id);

        return view('budgets.edit', compact('budget'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $userId = $this->getUserId();
        $budget = Budget::where('user_id', $userId)->findOrFail($id);

        $validated = $request->validate([
            'amount_limit'               => 'required|numeric|min:1',
            'alert_threshold_percentage' => 'nullable|integer|between:1,100',
            'notes'                      => 'nullable|string|max:255',
        ]);

        $oldValues = $budget->toArray();

        $budget->update([
            'amount_limit'               => $validated['amount_limit'],
            'alert_threshold_percentage' => $validated['alert_threshold_percentage'] ?? 80,
            'notes'                      => $validated['notes'] ?? null,
        ]);

        AuditLog::record($userId, 'UPDATE', 'budgets', $budget->id, $oldValues, $budget->toArray());

        return redirect()->route('budgets.index', [
            'month' => $budget->period_month,
            'year'  => $budget->period_year,
        ])->with('success', 'Budget updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $userId = $this->getUserId();
        $budget = Budget::where('user_id', $userId)->findOrFail($id);

        $oldValues = $budget->toArray();
        $budget->delete();

        AuditLog::record($userId, 'DELETE', 'budgets', $budget->id, $oldValues, null);

        return redirect()->route('budgets.index')->with('success', 'Budget deleted successfully.');
    }
}
