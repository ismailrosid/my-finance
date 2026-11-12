<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $table = 'budgets';

    protected $fillable = [
        'user_id',
        'category_id',
        'period_month',
        'period_year',
        'amount_limit',
        'alert_threshold_percentage',
        'notes',
    ];

    protected $casts = [
        'period_month'               => 'integer',
        'period_year'                => 'integer',
        'amount_limit'               => 'decimal:2',
        'alert_threshold_percentage' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('period_month', $month)->where('period_year', $year);
    }

    // Helpers
    public function getSpentAmountAttribute(): float
    {
        // Calculate actual spent amount from transactions for this category/period
        return (float) Transaction::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('transaction_type', 'expense')
            ->where('transaction_status', 'completed')
            ->where('is_expected', false)
            ->whereMonth('transaction_date', $this->period_month)
            ->whereYear('transaction_date', $this->period_year)
            ->sum('amount');
    }

    public function getUsagePercentageAttribute(): float
    {
        if ($this->amount_limit <= 0) {
            return 0;
        }

        return round(($this->getSpentAmountAttribute() / (float) $this->amount_limit) * 100, 1);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->amount_limit - $this->getSpentAmountAttribute());
    }

    public function isAlertThresholdReached(): bool
    {
        return $this->getUsagePercentageAttribute() >= $this->alert_threshold_percentage;
    }

    public function isOverBudget(): bool
    {
        return $this->getUsagePercentageAttribute() >= 100;
    }

    public function getPeriodLabel(): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March',
            4 => 'April',   5 => 'May',       6 => 'June',
            7 => 'July',    8 => 'August',    9 => 'September',
            10 => 'October', 11 => 'November', 12 => 'December',
        ];

        return ($months[$this->period_month] ?? $this->period_month) . ' ' . $this->period_year;
    }
}

