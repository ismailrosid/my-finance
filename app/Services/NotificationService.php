<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Installment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Check financial data and generate automated notifications if conditions are met.
     */
    public function checkAndGenerateNotifications(User $user): void
    {
        $this->checkDebtsAndInstallments($user);
        $this->checkBudgets($user);
        $this->checkLowBalances($user);
    }

    /**
     * Check unpaid installments due soon or overdue.
     */
    protected function checkDebtsAndInstallments(User $user): void
    {
        $daysAhead = (int) config('myfinance.debt_due_days_ahead', 3);
        $today = now()->startOfDay();
        $futureLimit = now()->addDays($daysAhead)->endOfDay();

        $unpaidInstallments = Installment::whereHas('debt', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('status', '!=', 'paid')
        ->where('due_date', '<=', $futureLimit)
        ->with('debt')
        ->get();

        foreach ($unpaidInstallments as $inst) {
            $debt = $inst->debt;
            if (!$debt) continue;

            $dueDate = $inst->due_date->startOfDay();
            $amountFormatted = 'Rp ' . number_format($inst->amount, 0, ',', '.');

            // Overdue
            if ($dueDate < $today) {
                $refId = "inst_{$inst->id}_overdue_" . now()->format('Y-m-d');
                if (!Notification::where('user_id', $user->id)->where('reference_id', $refId)->exists()) {
                    $daysLate = (int) $dueDate->diffInDays($today);
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'debt_overdue',
                        'title' => 'Tagihan Lewat Jatuh Tempo!',
                        'message' => "Cicilan #{$inst->installment_number} '{$debt->creditor_name}' ({$amountFormatted}) sudah lewat jatuh tempo {$daysLate} hari.",
                        'variant' => 'danger',
                        'action_url' => route('debts.index'),
                        'reference_id' => $refId,
                    ]);
                }
            }
            // Due Soon (today or within daysAhead)
            else {
                $refId = "inst_{$inst->id}_due_" . $dueDate->format('Y-m-d');
                if (!Notification::where('user_id', $user->id)->where('reference_id', $refId)->exists()) {
                    $diffDays = (int) $today->diffInDays($dueDate, false);
                    $timingText = $diffDays === 0 ? 'hari ini' : ($diffDays === 1 ? 'besok' : "dalam {$diffDays} hari ({$inst->due_date->format('d M')})");

                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'debt_due',
                        'title' => 'Pengingat Jatuh Tempo Cicilan',
                        'message' => "Cicilan #{$inst->installment_number} '{$debt->creditor_name}' ({$amountFormatted}) jatuh tempo {$timingText}.",
                        'variant' => 'warning',
                        'action_url' => route('debts.index'),
                        'reference_id' => $refId,
                    ]);
                }
            }
        }
    }

    /**
     * Check budget limit and threshold alerts.
     */
    protected function checkBudgets(User $user): void
    {
        $now = now();
        $budgets = Budget::where('user_id', $user->id)
            ->where('period_year', $now->year)
            ->where('period_month', $now->month)
            ->with('category')
            ->get();

        foreach ($budgets as $b) {
            $categoryName = $b->category?->category_name ?? 'Umum';
            $spent = $b->spent_amount;
            $limit = $b->amount_limit;
            $spentFormatted = 'Rp ' . number_format($spent, 0, ',', '.');
            $limitFormatted = 'Rp ' . number_format($limit, 0, ',', '.');

            // Overbudget
            if ($b->isOverBudget()) {
                $refId = "budget_over_{$b->id}_" . $now->format('Y-m');
                if (!Notification::where('user_id', $user->id)->where('reference_id', $refId)->exists()) {
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'budget_over',
                        'title' => 'Batas Anggaran Terlampaui!',
                        'message' => "Kategori '{$categoryName}' telah melebihi budget: {$spentFormatted} dari limit {$limitFormatted} ({$b->usage_percentage}%).",
                        'variant' => 'danger',
                        'action_url' => route('budgets.index'),
                        'reference_id' => $refId,
                    ]);
                }
            }
            // Warning Threshold reached (e.g. >= 80%)
            elseif ($b->isAlertThresholdReached()) {
                $refId = "budget_warn_{$b->id}_" . $now->format('Y-m');
                if (!Notification::where('user_id', $user->id)->where('reference_id', $refId)->exists()) {
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'budget_warning',
                        'title' => 'Peringatan Anggaran Menipis',
                        'message' => "Pengeluaran kategori '{$categoryName}' sudah mencapai {$b->usage_percentage}% dari budget bulanan ({$spentFormatted} / {$limitFormatted}).",
                        'variant' => 'warning',
                        'action_url' => route('budgets.index'),
                        'reference_id' => $refId,
                    ]);
                }
            }
        }
    }

    /**
     * Check active accounts with critical low balance.
     */
    protected function checkLowBalances(User $user): void
    {
        $threshold = (float) config('myfinance.low_balance_threshold', 200000);

        $accounts = Account::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('current_balance', '<', $threshold)
            ->get();

        foreach ($accounts as $acc) {
            $refId = "acc_low_{$acc->id}_" . now()->format('Y-m-d');
            if (!Notification::where('user_id', $user->id)->where('reference_id', $refId)->exists()) {
                $balFormatted = 'Rp ' . number_format($acc->current_balance, 0, ',', '.');
                $minFormatted = 'Rp ' . number_format($threshold, 0, ',', '.');

                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'low_balance',
                    'title' => 'Saldo Rekening Kritis',
                    'message' => "Saldo pada akun '{$acc->account_name}' tersisa {$balFormatted} (di bawah batas minimum {$minFormatted}).",
                    'variant' => 'warning',
                    'action_url' => route('accounts.index'),
                    'reference_id' => $refId,
                ]);
            }
        }
    }

    /**
     * Get unread notification count.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)->unread()->count();
    }

    /**
     * Get recent notifications list.
     */
    public function getRecentNotifications(User $user, int $limit = 15): Collection
    {
        return Notification::where('user_id', $user->id)
            ->latest('id')
            ->take($limit)
            ->get();
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(User $user, int $id): bool
    {
        $notification = Notification::where('user_id', $user->id)->where('id', $id)->first();
        if ($notification) {
            return $notification->markAsRead();
        }
        return false;
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}

