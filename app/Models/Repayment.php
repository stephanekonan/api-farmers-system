<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repayment extends Model
{
    protected $fillable = [
        'reference',
        'farmer_id',
        'operator_id',
        'commodity_kg',
        'commodity_rate_fcfa_per_kg',
        'total_fcfa_value',
        'notes',
        'repaid_at',
    ];

    protected $casts = [
        'commodity_kg' => 'decimal:3',
        'commodity_rate_fcfa_per_kg' => 'decimal:2',
        'total_fcfa_value' => 'decimal:2',
        'repaid_at' => 'datetime',
    ];

}
