<?php

namespace App\Enums;

enum PaymentMethodEnum: string
{
    case CASH = 'CASH';
    case CREDIT = 'CREDIT';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Paiement comptant',
            self::CREDIT => 'Paiement à crédit',
        };
    }
}