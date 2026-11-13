<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'transaction_code',
        'transaction_type',
        'category_id',
        'source_account_id',
        'destination_account_id',
        'amount',
        'fee_amount',
        'transaction_date',
        'transaction_time',
        'transaction_status',
        'is_expected',
        'debt_id',
        'installment_id',
        'description',
        'notes',
        'attachment_url',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'fee_amount'       => 'decimal:2',
        'transaction_date' => 'date',
        'is_expected'      => 'boolean',
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

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class, 'debt_id');
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class, 'installment_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    public function installmentPayments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class, 'transaction_id');
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeIncome($query)
    {
        return $query->where('transaction_type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('transaction_type', 'expense');
    }

    public function scopeTransfer($query)
    {
        return $query->where('transaction_type', 'transfer');
    }

    public function scopeDebtPayment($query)
    {
        return $query->where('transaction_type', 'debt_payment');
    }

    public function scopeCompleted($query)
    {
        return $query->where('transaction_status', 'completed');
    }

    public function scopeActual($query)
    {
        return $query->where('is_expected', false);
    }

    public function scopeExpected($query)
    {
        return $query->where('is_expected', true);
    }

    public function scopeInDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    public function scopeInMonth($query, int $month, int $year)
    {
        return $query->whereMonth('transaction_date', $month)->whereYear('transaction_date', $year);
    }

    // Helpers
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getTypeLabel(): string
    {
        return match ($this->transaction_type) {
            'income'       => 'Income',
            'expense'      => 'Expense',
            'transfer'     => 'Transfer',
            'debt_payment' => 'Debt Payment',
            default        => 'Unknown',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->transaction_status) {
            'completed' => 'Completed',
            'pending'   => 'Pending',
            'cancelled' => 'Cancelled',
            default     => 'Unknown',
        };
    }

    // Auto-generate transaction code before creating
    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (empty($transaction->transaction_code)) {
                $transaction->transaction_code = 'TXN-' . strtoupper(uniqid());
            }
        });
    }
}

