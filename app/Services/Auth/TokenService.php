<?php

namespace App\Services\Auth;

use App\Contracts\Services\Auth\TokenServiceInterface;
use App\Models\User;

class TokenService implements TokenServiceInterface
{
    private const TOKEN_LIFETIME_HOURS = 12;

    public function issueToken(User $user, string $deviceName, string $ip): array
    {
        $expiresAt = now()->addHours(self::TOKEN_LIFETIME_HOURS);

        $token = $user->createToken(
            name: "api_{$user->role->value}",
            abilities: $user->tokenAbilities(),
            expiresAt: $expiresAt,
        );

        $token->accessToken->update([
            'device_name' => $deviceName,
            'ip_address' => $ip,
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => $user,
        ];
    }

    public function revokeToken(User $user, string $tokenId): bool
    {
        return (bool) $user->tokens()->where('id', $tokenId)->delete();
    }

    public function revokeAllTokens(User $user): int
    {
        return $user->tokens()->delete();
    }

    public function getActiveTokens(User $user): array
    {
        return $user->tokens()
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->get()
            ->map(fn($token) => [
                'id' => $token->id,
                'device_name' => $token->device_name,
                'ip_address' => $token->ip_address,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'expires_at' => $token->expires_at,
            ])
            ->toArray();
    }
}
