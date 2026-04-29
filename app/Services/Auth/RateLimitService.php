<?php

namespace App\Services\Auth;

use App\Contracts\Services\Auth\RateLimitServiceInterface;
use App\Models\LoginAttempt;
use Illuminate\Validation\ValidationException;
use function sprintf;

class RateLimitService implements RateLimitServiceInterface
{
    private const WINDOW_MINUTES = 15;
    private const MAX_ATTEMPTS_EMAIL = 5;
    private const MAX_ATTEMPTS_IP = 10;

    public function checkLoginAttempts(string $email, string $ip): void
    {
        if ($this->isBlocked($email, $ip)) {
            throw ValidationException::withMessages([
                'email' => [
                    sprintf(
                        'Trop de tentatives. Réessayez dans %d minutes.',
                        self::WINDOW_MINUTES
                    )
                ],
            ]);
        }
    }

    public function recordAttempt(string $email, string $ip, ?string $userAgent, bool $successful): void
    {
        LoginAttempt::create([
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'attempted_at' => now(),
        ]);
    }

    public function isBlocked(string $email, string $ip): bool
    {
        $since = now()->subMinutes(self::WINDOW_MINUTES);

        $emailAttempts = LoginAttempt::where('email', $email)
            ->where('successful', false)
            ->where('attempted_at', '>=', $since)
            ->count();

        if ($emailAttempts >= self::MAX_ATTEMPTS_EMAIL) {
            return true;
        }

        $ipAttempts = LoginAttempt::where('ip_address', $ip)
            ->where('successful', false)
            ->where('attempted_at', '>=', $since)
            ->count();

        return $ipAttempts >= self::MAX_ATTEMPTS_IP;
    }

    public function getRemainingAttempts(string $email, string $ip): int
    {
        $since = now()->subMinutes(self::WINDOW_MINUTES);

        $emailAttempts = LoginAttempt::where('email', $email)
            ->where('successful', false)
            ->where('attempted_at', '>=', $since)
            ->count();

        $ipAttempts = LoginAttempt::where('ip_address', $ip)
            ->where('successful', false)
            ->where('attempted_at', '>=', $since)
            ->count();

        return max(
            self::MAX_ATTEMPTS_EMAIL - $emailAttempts,
            self::MAX_ATTEMPTS_IP - $ipAttempts
        );
    }
}
