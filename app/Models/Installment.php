<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installment extends Model
{
    protected $table = 'installments';

    protected $fillable = [
        'debt_id',
        'installment_number',
        'due_date',
        'amount',
        'principal_amount',
        'interest_amount',
        'fee_amount',
        'paid_amount',
        'status',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'due_date'        => 'date',
        'payment_date'    => 'date',
        'amount'          => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'fee_amount'      => 'decimal:2',
        'paid_amount'     => 'decimal:2',
    ];

    // Relationships
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class, 'debt_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class, 'installment_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'installment_id', 'id');
    }

    // Scopes
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeDueBefore($query, string $date)
    {
        return $query->where('due_date', '<=', $date);
    }

    // Helpers
    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'unpaid'         => 'Unpaid',
            'partially_paid' => 'Partial',
            'paid'           => 'Paid',
            'overdue'        => 'Overdue',
            default          => 'Unknown',
        };
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date->isPast();
    }
}

