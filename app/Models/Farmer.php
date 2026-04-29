<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Farmer extends Model
{
    use HasFactory, SoftDeletes;

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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(Repayment::class);
    }
}
