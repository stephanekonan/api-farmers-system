<?php

namespace App\Exceptions\Category;

use Exception;

class CategoryNotFoundException extends Exception
{
    public static function notFound(int $id): self
    {
        return new self("Catégorie introuvable.");
    }

    public static function slugNotFound(string $slug): self
    {
        return new self("Aucune catégorie trouvée avec le slug '{$slug}'.");
    }

    public static function parentNotFound(int $parentId): self
    {
        return new self("Catégorie parente introuvable.");
    }
}