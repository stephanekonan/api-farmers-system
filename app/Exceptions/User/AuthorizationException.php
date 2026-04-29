<?php

namespace App\Exceptions\User;

use Exception;

class AuthorizationException extends Exception
{
    public static function cannotManageUsers(): self
    {
        return new self('Les opérateurs ne peuvent pas gérer les utilisateurs.');
    }

    public static function cannotManageAdmins(): self
    {
        return new self('Les superviseurs ne peuvent pas créer ou modifier d\'administrateurs.');
    }

    public static function cannotModifyOtherOperators(): self
    {
        return new self('Vous ne pouvez modifier que vos propres opérateurs.');
    }

    public static function cannotModifyAdmins(): self
    {
        return new self('Seul un administrateur peut modifier un autre administrateur.');
    }

    public static function cannotDeleteUserWithSubordinates(): self
    {
        return new self('Impossible de supprimer un utilisateur qui a des subordonnés.');
    }
}
