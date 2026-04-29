<?php

namespace App\Contracts\Services\Auth;

use App\Models\User;

interface TokenServiceInterface
{
    public function issueToken(User $user, string $deviceName, string $ip): array;

    public function revokeToken(User $user, string $tokenId): bool;

    public function revokeAllTokens(User $user): int;

    public function getActiveTokens(User $user): array;
}
