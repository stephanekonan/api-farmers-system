<?php

namespace App\Exceptions\Repayment;

use Exception;

class RepaymentValidationException extends Exception
{
    public static function cannotDeleteRepaymentWithDebts(): self
    {
        return new self("Impossible de supprimer un remboursement ayant des dettes associées.");
    }

    public static function farmerNotFound(int $farmerId): self
    {
        return new self("Agriculteur introuvable.");
    }

    public static function farmerNotActive(): self
    {
        return new self("L'agriculteur n'est pas actif.");
    }

    public static function operatorNotFound(int $operatorId): self
    {
        return new self("Opérateur introuvable.");
    }

    public static function invalidCommodityKg(): self
    {
        return new self("Le poids de la marchandise doit être supérieur à zéro.");
    }

    public static function invalidCommodityRate(): self
    {
        return new self("Le taux de la marchandise ne peut pas être négatif.");
    }

    public static function invalidTotalValue(): self
    {
        return new self("La valeur totale ne peut pas être négative.");
    }

    public static function noDebtAllocationsProvided(): self
    {
        return new self("Aucune allocation de dette n'a été fournie.");
    }

    public static function invalidAllocationFormat(): self
    {
        return new self("Format d'allocation de dette invalide.");
    }

    public static function debtNotFound(int $debtId): self
    {
        return new self("Dette introuvable.");
    }

    public static function invalidAllocationAmount(): self
    {
        return new self("Le montant alloué doit être supérieur à zéro.");
    }

    public static function allocationExceedsRemainingAmount(): self
    {
        return new self("Le montant alloué dépasse le montant restant dû.");
    }

    public static function invalidTotalAllocation(): self
    {
        return new self("Le total des allocations est invalide.");
    }

    public static function duplicateReference(string $reference): self
    {
        return new self("La référence de remboursement '{$reference}' existe déjà.");
    }
}
