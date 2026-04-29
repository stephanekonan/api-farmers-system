<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Debt extends Model
{
    protected $fillable = [
        'farmer_id',
        'transaction_id',
        'original_amount_fcfa',
        'paid_amount_fcfa',
        'remaining_amount_fcfa',
        'status',
        'incurred_at',
        'fully_paid_at',
    ];

    protected $casts = [
        'original_amount_fcfa' => 'decimal:2',
        'paid_amount_fcfa' => 'decimal:2',
        'remaining_amount_fcfa' => 'decimal:2',
        'incurred_at' => 'datetime',
        'fully_paid_at' => 'datetime',
    ];

}
