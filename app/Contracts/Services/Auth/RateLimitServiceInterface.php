<?php

namespace App\Contracts\Services\Auth;

interface RateLimitServiceInterface
{
    public function checkLoginAttempts(string $email, string $ip): void;

    public function recordAttempt(string $email, string $ip, ?string $userAgent, bool $successful): void;

    public function isBlocked(string $email, string $ip): bool;

    public function getRemainingAttempts(string $email, string $ip): int;
}
