<?php

namespace App\Services;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use function sprintf;
class AuthService
{
    private const WINDOW_MINUTES = 15;
    private const MAX_ATTEMPTS_EMAIL = 5;
    private const MAX_ATTEMPTS_IP = 10;
    private const TOKEN_LIFETIME_HOURS = 12;

    public function login(
        string $email,
        string $password,
        string $deviceName,
        string $ip,
        ?string $userAgent = null,
    ): array {
        $this->checkRateLimit($email, $ip);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            $this->recordAttempt($email, $ip, $userAgent, successful: false);

            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        if (!$user->is_active) {
            $this->recordAttempt($email, $ip, $userAgent, successful: false);

            throw ValidationException::withMessages([
                'email' => ['Ce compte a été désactivé. Contactez votre administrateur.'],
            ]);
        }

        $this->recordAttempt($email, $ip, $userAgent, successful: true);

        $user->tokens()
            ->where('device_name', $deviceName)
            ->delete();

        return $this->issueToken($user, $deviceName, $ip);
    }

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

    protected function checkRateLimit(string $email, string $ip): void
    {
        $since = now()->subMinutes(self::WINDOW_MINUTES);

        $emailAttempts = LoginAttempt::where('email', $email)
            ->where('successful', false)
            ->where('attempted_at', '>=', $since)
            ->count();

        if ($emailAttempts >= self::MAX_ATTEMPTS_EMAIL) {
            throw ValidationException::withMessages([
                'email' => [
                    sprintf(
                        'Trop de tentatives. Réessayez dans %d minutes.',
                        self::WINDOW_MINUTES
                    )
                ],
            ]);
        }

        $ipAttempts = LoginAttempt::where('ip_address', $ip)
            ->where('successful', false)
            ->where('attempted_at', '>=', $since)
            ->count();

        if ($ipAttempts >= self::MAX_ATTEMPTS_IP) {
            throw ValidationException::withMessages([
                'email' => ['Trop de requêtes depuis cette adresse. Réessayez plus tard.'],
            ]);
        }
    }

    protected function recordAttempt(
        string $email,
        string $ip,
        ?string $userAgent,
        bool $successful
    ): void {
        LoginAttempt::create([
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'attempted_at' => now(),
        ]);
    }
}