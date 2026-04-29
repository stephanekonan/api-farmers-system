<?php

namespace App\Exceptions\Farmer;

use Exception;

class FarmerValidationException extends Exception
{
    public static function invalidCreditLimit(): self
    {
        return new self('Le plafond de crédit ne peut pas être négatif.');
    }

    public static function invalidDebtAmount(): self
    {
        return new self('Le montant de la dette ne peut pas être négatif.');
    }

    public static function duplicateCardIdentifier(string $cardIdentifier): self
    {
        return new self("Un agriculteur avec cet identifiant de carte existe déjà.");
    }

    public static function duplicatePhone(string $phone): self
    {
        return new self("Un agriculteur avec ce numéro de téléphone existe déjà.");
    }

    public static function cannotDeleteFarmerWithTransactions(): self
    {
        return new self("Impossible de supprimer un agriculteur ayant des transactions associées.");
    }

    public static function cannotDeleteFarmerWithDebts(): self
    {
        return new self("Impossible de supprimer un agriculteur ayant des dettes en cours.");
    }
}