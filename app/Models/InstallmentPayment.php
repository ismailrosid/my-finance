<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    protected $table = 'installment_payments';

    protected $fillable = [
        'installment_id',
        'transaction_id',
        'account_id',
        'payment_date',
        'amount_paid',
        'principal_paid',
        'interest_paid',
        'notes',
    ];

    protected $casts = [
        'payment_date'   => 'date',
        'amount_paid'    => 'decimal:2',
        'principal_paid' => 'decimal:2',
        'interest_paid'  => 'decimal:2',
    ];

    // Relationships
    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class, 'installment_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}

