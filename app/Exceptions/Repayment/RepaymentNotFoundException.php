<?php

namespace App\Exceptions\Repayment;

use Exception;

class RepaymentNotFoundException extends Exception
{
    public static function notFound(int $id): self
    {
        return new self("Remboursement introuvable.");
    }

    public static function referenceNotFound(string $reference): self
    {
        return new self("Aucun remboursement trouvé avec la référence '{$reference}'.");
    }
}
