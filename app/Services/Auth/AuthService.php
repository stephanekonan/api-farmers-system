<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\Auth\AuthenticationException;
use App\Contracts\Services\Auth\AuthServiceInterface;
use App\Contracts\Services\Auth\RateLimitServiceInterface;
use App\Contracts\Services\Auth\TokenServiceInterface;

class AuthService implements AuthServiceInterface
{
    private const WINDOW_MINUTES = 15;
    private const MAX_ATTEMPTS_EMAIL = 5;
    private const MAX_ATTEMPTS_IP = 10;
    private const TOKEN_LIFETIME_HOURS = 12;

    public function __construct(
        private RateLimitServiceInterface $rateLimitService,
        private TokenServiceInterface $tokenService,
        private Hash $hash
    ) {
    }

    public function login(
        string $email,
        string $password,
        string $deviceName,
        string $ip,
        ?string $userAgent = null,
    ): array {
        $this->rateLimitService->checkLoginAttempts($email, $ip);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $this->rateLimitService->recordAttempt($email, $ip, $userAgent, successful: false);
            throw AuthenticationException::invalidCredentials();
        }

        if (!$user->is_active) {
            $this->rateLimitService->recordAttempt($email, $ip, $userAgent, successful: false);
            throw AuthenticationException::accountDisabled();
        }

        $this->rateLimitService->recordAttempt($email, $ip, $userAgent, successful: true);

        $user->tokens()
            ->where('device_name', $deviceName)
            ->delete();

        return $this->tokenService->issueToken($user, $deviceName, $ip);
    }

    public function logout(User $user, string $tokenId): bool
    {
        return $this->tokenService->revokeToken($user, $tokenId);
    }

    public function logoutAll(User $user): int
    {
        return $this->tokenService->revokeAllTokens($user);
    }

    public function refreshToken(User $user, string $deviceName, string $ip): array
    {
        return $this->tokenService->issueToken($user, $deviceName, $ip);
    }

    public function getActiveSessions(User $user): array
    {
        return $this->tokenService->getActiveTokens($user);
    }

    public function revokeSession(User $user, int $tokenId): bool
    {
        return $this->tokenService->revokeToken($user, $tokenId);
    }
}