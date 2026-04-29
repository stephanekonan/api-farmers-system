<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;
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

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function debt(): HasOne
    {
        return $this->hasOne(Debt::class);
    }
}
