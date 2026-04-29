<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepaymentDebt extends Model
{
    protected $fillable = [
        'repayment_id',
        'debt_id',
        'amount_applied_fcfa',
    ];

    protected $casts = [
        'amount_applied_fcfa' => 'decimal:2',
    ];

}
