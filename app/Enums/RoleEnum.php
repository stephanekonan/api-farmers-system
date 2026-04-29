<?php

namespace App\Enums;
use function in_array;

enum RoleEnum: string
{
    case ADMIN = 'ADMIN';
    case SUPERVISOR = 'SUPERVISOR';
    case OPERATOR = 'OPERATOR';

    public function canManageUsers(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPERVISOR]);
    }

    public function canManageProducts(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPERVISOR]);
    }
}