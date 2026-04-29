<?php

namespace App\Exceptions\Repayment;

use Exception;

class RepaymentNotFoundException extends Exception
{
    public static function notFound(): self
    {
        return new self("Remboursement introuvable.");
    }

    public static function referenceNotFound(): self
    {
        return new self("Aucun remboursement trouvé");
    }
}
