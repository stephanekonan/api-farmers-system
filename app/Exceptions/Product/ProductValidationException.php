<?php

namespace App\Exceptions\Product;

use Exception;

class ProductValidationException extends Exception
{
    public static function invalidPrice(): self
    {
        return new self('Le prix ne peut pas être négatif.');
    }

    public static function duplicateProductName(string $name): self
    {
        return new self("Ce produit existe déjà.");
    }

    public static function cannotDeleteProductWithTransactions(): self
    {
        return new self("Impossible de supprimer un produit ayant des transactions associées.");
    }
}