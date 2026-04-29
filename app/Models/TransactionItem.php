<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_name',
        'unit_price_fcfa',
        'quantity',
        'unit',
        'line_total_fcfa',
    ];

    protected $casts = [
        'unit_price_fcfa' => 'decimal:2',
        'quantity' => 'decimal:3',
        'line_total_fcfa' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
