<?php

namespace App\Exceptions\Debt;

use Exception;

class DebtNotFoundException extends Exception
{
    public static function notFound(int $id): self
    {
        return new self("Dette introuvable.");
    }

    public static function farmerNotFound(int $farmerId): self
    {
        return new self("Aucune dette trouvée pour cet agriculteur.");
    }

    public static function transactionNotFound(int $transactionId): self
    {
        return new self("Aucune dette trouvée pour cette transaction.");
    }
}
