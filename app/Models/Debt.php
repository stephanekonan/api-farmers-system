<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Debt extends Model
{
    use HasFactory;
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

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function repayments(): BelongsToMany
    {
        return $this->belongsToMany(Repayment::class, 'repayment_debt')
            ->withPivot('amount_applied_fcfa')
            ->withTimestamps();
    }
}
