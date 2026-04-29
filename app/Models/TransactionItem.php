<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
