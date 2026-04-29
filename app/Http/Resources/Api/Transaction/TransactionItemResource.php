<?php

namespace App\Http\Resources\Api\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'product_name' => $this->product->product_name,
                    'unit' => $this->product->unit,
                    'price_fcfa' => $this->product->price_fcfa,
                ];
            }),
            'product_name' => $this->product_name,
            'unit_price_fcfa' => $this->unit_price_fcfa,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'line_total_fcfa' => $this->line_total_fcfa,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
