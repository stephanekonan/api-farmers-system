<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\Api\TransactionItemResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,

            'farmer' => $this->whenLoaded(
                'farmer',
                fn() =>
                new FarmerResource($this->farmer)
            ),

            'operator' => $this->whenLoaded(
                'operator',
                fn() =>
                new OperatorResource($this->operator)
            ),

            'subtotal_fcfa' => $this->subtotal_fcfa,
            'payment_method' => $this->payment_method,

            'interest_rate' => $this->interest_rate,
            'interest_amount_fcfa' => $this->interest_amount_fcfa,

            'total_fcfa' => $this->total_fcfa,
            'status' => $this->status,
            'notes' => $this->notes,
            'transacted_at' => $this->transacted_at,

            'items' => TransactionItemResource::collection(
                $this->whenLoaded('transactionItems')
            ),

            'debt' => $this->when(
                $this->payment_method === 'credit' && $this->relationLoaded('debt'),
                fn() => new DebtResource($this->debt)
            ),
        ];
    }
}
