<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Farmer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'card_identifier',
        'firstname',
        'lastname',
        'phone',
        'village',
        'region',
        'credit_limit_fcfa',
        'total_outstanding_debt',
        'is_active',
    ];

    protected $casts = [
        'credit_limit_fcfa' => 'decimal:2',
        'total_outstanding_debt' => 'decimal:2',
        'is_active' => 'boolean',
    ];

}
