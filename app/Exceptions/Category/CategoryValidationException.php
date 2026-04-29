<?php

namespace App\Exceptions\Category;

use Exception;

class CategoryValidationException extends Exception
{
    public static function cannotDeleteCategoryWithChildren(): self
    {
        return new self('Impossible de supprimer une catégorie contenant des sous-catégories.');
    }

    public static function cannotDeleteCategoryWithProducts(): self
    {
        return new self('Impossible de supprimer une catégorie contenant des produits associés.');
    }

    public static function cannotBeOwnParent(): self
    {
        return new self('Une catégorie ne peut pas être son propre parent.');
    }

    public static function cannotCreateCircularReference(): self
    {
        return new self('Impossible de créer une relation circulaire dans la hiérarchie des catégories.');
    }

    public static function duplicateCategoryName(string $name): self
    {
        return new self("Une catégorie avec le nom '{$name}' existe déjà.");
    }

    public static function cannotMoveToSelf(): self
    {
        return new self('Impossible de déplacer une catégorie sur elle-même.');
    }

    public static function cannotMoveToDescendant(): self
    {
        return new self('Impossible de déplacer une catégorie vers une de ses sous-catégories.');
    }
}