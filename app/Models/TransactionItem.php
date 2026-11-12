<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $table = 'transaction_items';

    protected $fillable = [
        'transaction_id',
        'item_name',
        'quantity',
        'unit_name',
        'unit_price',
        'total_price',
        'category_id',
        'notes',
    ];

    protected $casts = [
        'quantity'    => 'decimal:2',
        'unit_price'  => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Relationships
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Helper: auto-calculate total_price
    protected static function booted(): void
    {
        static::saving(function (TransactionItem $item) {
            if ($item->quantity && $item->unit_price) {
                $item->total_price = round((float) $item->quantity * (float) $item->unit_price, 2);
            }
        });
    }
}

