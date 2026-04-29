<?php

namespace App\Exceptions\Farmer;

use Exception;

class FarmerNotFoundException extends Exception
{
    public static function notFound(int $id): self
    {
        return new self("Agriculteur introuvable.");
    }

    public static function cardIdentifierNotFound(string $cardIdentifier): self
    {
        return new self("Aucun agriculteur trouvé avec l'identifiant de cette carte.");
    }

    public static function phoneNotFound(string $phone): self
    {
        return new self("Aucun agriculteur trouvé avec ce numéro de téléphone.");
    }
}