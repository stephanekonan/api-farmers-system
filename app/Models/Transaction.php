<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'reference',
        'farmer_id',
        'operator_id',
        'subtotal_fcfa',
        'payment_method',
        'interest_rate',
        'interest_amount_fcfa',
        'total_fcfa',
        'status',
        'notes',
        'transacted_at',
    ];

    protected $casts = [
        'subtotal_fcfa' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'interest_amount_fcfa' => 'decimal:2',
        'total_fcfa' => 'decimal:2',
        'transacted_at' => 'datetime',
    ];

}
