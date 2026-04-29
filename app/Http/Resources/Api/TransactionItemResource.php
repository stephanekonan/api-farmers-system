<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,

            'product' => $this->whenLoaded(
                'product',
                fn() =>
                new ProductMiniResource($this->product)
            ),

            'product_name' => $this->product_name,

            'unit_price_fcfa' => $this->unit_price_fcfa,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'line_total_fcfa' => $this->line_total_fcfa,
        ];
    }
}
