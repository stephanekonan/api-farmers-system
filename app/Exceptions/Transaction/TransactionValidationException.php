<?php

namespace App\Exceptions\Transaction;

use Exception;

class TransactionValidationException extends Exception
{
    public static function noItemsProvided(): self
    {
        return new self("Aucun article n'a été fourni pour la transaction.");
    }

    public static function cannotDeleteTransactionWithItems(): self
    {
        return new self("Impossible de supprimer une transaction contenant des articles.");
    }

    public static function cannotDeleteTransactionWithDebt(): self
    {
        return new self("Impossible de supprimer une transaction ayant une dette associée.");
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

    public static function invalidPaymentMethod(): self
    {
        return new self("Méthode de paiement invalide.");
    }

    public static function invalidInterestRate(): self
    {
        return new self("Le taux d'intérêt ne peut pas être négatif.");
    }

    public static function productNotFound(int $productId): self
    {
        return new self("Produit introuvable.");
    }

    public static function productNotActive(): self
    {
        return new self("Le produit n'est pas actif.");
    }

    public static function invalidQuantity(): self
    {
        return new self("La quantité doit être supérieure à zéro.");
    }

    public static function invalidUnitPrice(): self
    {
        return new self("Le prix unitaire ne peut pas être négatif.");
    }

    public static function duplicateReference(string $reference): self
    {
        return new self("La référence de transaction '{$reference}' existe déjà.");
    }
}
