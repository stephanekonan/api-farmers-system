<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Repayment extends Model
{
    use HasFactory;
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

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function debts(): BelongsToMany
    {
        return $this->belongsToMany(Debt::class, 'repayment_debt')
            ->withPivot('amount_applied_fcfa')
            ->withTimestamps();
    }
}
