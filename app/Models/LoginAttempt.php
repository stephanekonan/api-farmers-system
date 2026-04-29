<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LoginAttempt extends Model
{
    protected $table = 'login_attempts';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'ip_address',
        'successful',
        'user_agent',
        'attempted_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('successful', true);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('successful', false);
    }

    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    public function scopeByIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeRecent(Builder $query, int $minutes = 5): Builder
    {
        return $query->where('attempted_at', '>=', now()->subMinutes($minutes));
    }
}