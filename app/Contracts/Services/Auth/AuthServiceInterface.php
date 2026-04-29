<?php

namespace App\Contracts\Services\Auth;

use App\Models\User;

interface AuthServiceInterface
{
    public function login(
        string $email,
        string $password,
        string $deviceName,
        string $ip,
        ?string $userAgent = null,
    ): array;

    public function logout(User $user, string $tokenId): bool;

    public function logoutAll(User $user): int;

    public function refreshToken(User $user, string $deviceName, string $ip): array;

    public function getActiveSessions(User $user): array;

    public function revokeSession(User $user, int $tokenId): bool;
}
