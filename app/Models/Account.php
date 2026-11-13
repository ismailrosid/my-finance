<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'accounts';

    protected $fillable = [
        'user_id',
        'account_name',
        'account_type',
        'account_number',
        'bank_name',
        'currency_code',
        'initial_balance',
        'current_balance',
        'color',
        'icon',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function outgoingTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'source_account_id');
    }

    public function incomingTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'destination_account_id');
    }

    public function installmentPayments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class, 'account_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helpers
    public function getFormattedBalanceAttribute(): string
    {
        return 'Rp ' . number_format($this->current_balance, 0, ',', '.');
    }

    public function getAccountTypeLabel(): string
    {
        return match ($this->account_type) {
            'bank'        => 'Bank',
            'e_wallet'    => 'E-Wallet',
            'cash'        => 'Cash',
            'credit_card' => 'Credit Card',
            'investment'  => 'Investment',
            default       => 'Other',
        };
    }
}

