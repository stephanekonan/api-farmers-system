<?php

namespace App\Exceptions\Auth;

use Exception;

class AuthenticationException extends Exception
{
    public static function invalidCredentials(): self
    {
        return new self('Identifiants incorrects.');
    }

    public static function accountDisabled(): self
    {
        return new self('Ce compte a été désactivé. Contactez votre administrateur.');
    }

    public static function tooManyAttempts(int $minutes): self
    {
        return new self("Trop de tentatives. Réessayez dans {$minutes} minutes.");
    }

    public static function ipBlocked(): self
    {
        return new self('Trop de requêtes depuis cette adresse. Réessayez plus tard.');
    }
}
