<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class DebtMiniResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,

            'reference' => $this->whenLoaded(
                'transaction',
                fn() =>
                $this->transaction->reference
            ),

            'original_amount_fcfa' => $this->original_amount_fcfa,
            'status' => $this->status,
            'incurred_at' => $this->incurred_at,
        ];
    }
}