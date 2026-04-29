<?php

namespace App\Exceptions\Transaction;

use Exception;

class TransactionNotFoundException extends Exception
{
    public static function notFound(int $id): self
    {
        return new self("Transaction introuvable.");
    }

    public static function referenceNotFound(string $reference): self
    {
        return new self("Aucune transaction trouvée avec la référence '{$reference}'.");
    }

    public static function itemNotFound(int $itemId): self
    {
        return new self("Article de transaction introuvable.");
    }
}
