<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Models\Repayment;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use function in_array;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'created_by',
        'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => RoleEnum::class,
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'operator_id');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(Repayment::class, 'operator_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === RoleEnum::ADMIN;
    }
    public function isSupervisor(): bool
    {
        return $this->role === RoleEnum::SUPERVISOR;
    }
    public function isOperator(): bool
    {
        return $this->role === RoleEnum::OPERATOR;
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, [RoleEnum::ADMIN, RoleEnum::SUPERVISOR]);
    }

    public function tokenAbilities(): array
    {
        return match ($this->role) {
            RoleEnum::ADMIN => ['*'],
            RoleEnum::SUPERVISOR => [
                'users:read',
                'users:write',
                'products:read',
                'products:write',
                'categories:read',
                'categories:write',
                'farmers:read',
                'farmers:write',
                'transactions:read',
                'transactions:write',
                'repayments:read',
                'repayments:write',
                'debts:read',
            ],
            RoleEnum::OPERATOR => [
                'products:read',
                'categories:read',
                'farmers:read',
                'farmers:write',
                'transactions:read',
                'transactions:write',
                'repayments:read',
                'repayments:write',
                'debts:read',
            ],
        };
    }
}
