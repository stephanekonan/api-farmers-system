<?php

namespace App\Exceptions\Product;

use Exception;

class ProductNotFoundException extends Exception
{
    public static function notFound(int $id): self
    {
        return new self("Produit introuvable.");
    }

    public static function slugNotFound(string $slug): self
    {
        return new self("Aucun produit trouvé avec le slug '{$slug}'.");
    }

    public static function categoryNotFound(int $categoryId): self
    {
        return new self("Catégorie introuvable.");
    }
}