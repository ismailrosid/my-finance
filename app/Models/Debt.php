<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'debts';

    protected $fillable = [
        'user_id',
        'debt_code',
        'debt_type',
        'direction',
        'creditor_name',
        'principal_amount',
        'interest_rate',
        'interest_period',
        'interest_method',
        'fee_amount',
        'admin_fee_type',
        'admin_fee_value',
        'interest_amount',
        'total_amount',
        'remaining_principal',
        'start_date',
        'due_date',
        'tenor_months',
        'tenor_value',
        'tenor_unit',
        'repayment_frequency',
        'status',
        'notes',
    ];

    protected $casts = [
        'principal_amount'    => 'decimal:2',
        'interest_rate'       => 'decimal:2',
        'fee_amount'          => 'decimal:2',
        'admin_fee_value'     => 'decimal:2',
        'interest_amount'     => 'decimal:2',
        'total_amount'        => 'decimal:2',
        'remaining_principal' => 'decimal:2',
        'start_date'          => 'date',
        'due_date'            => 'date',
        'tenor_months'        => 'integer',
        'tenor_value'         => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class, 'debt_id')->orderBy('installment_number');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'debt_id');
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePayable($query)
    {
        return $query->where('direction', 'payable');
    }

    public function scopeReceivable($query)
    {
        return $query->where('direction', 'receivable');
    }

    // Helpers
    public function getFormattedRemainingAttribute(): string
    {
        return 'Rp ' . number_format($this->remaining_principal, 0, ',', '.');
    }

    public function getFormattedTenorAttribute(): string
    {
        $value = $this->tenor_value ?: ($this->tenor_months ?: 1);
        $unit = $this->tenor_unit ? ucfirst($this->tenor_unit) : 'Months';
        return "{$value} {$unit}";
    }

    public function getFormattedInterestAttribute(): string
    {
        if ((float) $this->interest_rate <= 0) {
            return '0% (No Interest)';
        }

        $period = ucfirst($this->interest_period ?: 'annual');
        $method = ucfirst($this->interest_method ?: 'flat');
        $rateFormatted = rtrim(rtrim(number_format((float) $this->interest_rate, 2), '0'), '.');

        return "{$rateFormatted}% {$period} ({$method})";
    }

    public function getFormattedAdminFeeAttribute(): string
    {
        if ((float) $this->fee_amount <= 0) {
            return 'Rp 0';
        }

        $feeFormatted = 'Rp ' . number_format($this->fee_amount, 0, ',', '.');
        if ($this->admin_fee_type === 'percentage') {
            $valFormatted = rtrim(rtrim(number_format((float) $this->admin_fee_value, 2), '0'), '.');
            return "{$feeFormatted} ({$valFormatted}%)";
        }

        return $feeFormatted;
    }

    public function getRepaymentFrequencyLabelAttribute(): string
    {
        return match ($this->repayment_frequency) {
            'single'   => 'Lump Sum (At Maturity)',
            'daily'    => 'Daily',
            'weekly'   => 'Weekly',
            'annually' => 'Annually',
            default    => 'Monthly',
        };
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->remaining_principal;
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return round(($this->getPaidAmountAttribute() / (float) $this->total_amount) * 100, 1);
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'active'    => 'Active',
            'paid_off'  => 'Paid Off',
            'defaulted' => 'Defaulted',
            'cancelled' => 'Cancelled',
            default     => 'Unknown',
        };
    }

    // Auto-generate debt code before creating
    protected static function booted(): void
    {
        static::creating(function (Debt $debt) {
            if (empty($debt->debt_code)) {
                $debt->debt_code = 'DBT-' . strtoupper(uniqid());
            }
        });
    }
}

