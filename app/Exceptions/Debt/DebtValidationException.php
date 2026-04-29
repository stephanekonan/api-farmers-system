<?php

namespace App\Exceptions\Debt;

use Exception;

class DebtValidationException extends Exception
{
    public static function cannotDeleteDebtWithRepayments(): self
    {
        return new self("Impossible de supprimer une dette ayant des remboursements.");
    }

    public static function farmerNotFound(int $farmerId): self
    {
        return new self("Agriculteur introuvable.");
    }

    public static function invalidOriginalAmount(): self
    {
        return new self("Le montant original ne peut pas être négatif.");
    }

    public static function invalidPaidAmount(): self
    {
        return new self("Le montant payé ne peut pas être négatif.");
    }

    public static function invalidRemainingAmount(): self
    {
        return new self("Le montant restant ne peut pas être négatif.");
    }

    public static function invalidStatus(): self
    {
        return new self("Statut de dette invalide.");
    }

    public static function invalidPaymentAmount(): self
    {
        return new self("Le montant du paiement doit être supérieur à zéro.");
    }

    public static function paymentExceedsRemainingAmount(): self
    {
        return new self("Le montant du paiement dépasse le montant restant dû.");
    }
}
