<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_name',
        'slug',
        'description',
        'category_id',
        'price_fcfa',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'price_fcfa' => 'decimal:2',
        'is_active' => 'boolean',
    ];

}
