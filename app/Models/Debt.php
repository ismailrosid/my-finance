<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use SoftDeletes;

    protected $table = 'debts';

    protected $fillable = [
        'user_id',
        'debt_code',
        'debt_type',
        'direction',
        'creditor_name',
        'principal_amount',
        'interest_rate',
        'fee_amount',
        'total_amount',
        'remaining_principal',
        'start_date',
        'due_date',
        'tenor_months',
        'status',
        'notes',
    ];

    protected $casts = [
        'principal_amount'    => 'decimal:2',
        'interest_rate'       => 'decimal:2',
        'fee_amount'          => 'decimal:2',
        'total_amount'        => 'decimal:2',
        'remaining_principal' => 'decimal:2',
        'start_date'          => 'date',
        'due_date'            => 'date',
        'tenor_months'        => 'integer',
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

