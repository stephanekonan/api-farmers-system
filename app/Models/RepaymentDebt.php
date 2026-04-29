<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function repayment(): BelongsTo
    {
        return $this->belongsTo(Repayment::class);
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }
}
