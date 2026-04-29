<?php

namespace App\Http\Resources\Api\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'farmer' => $this->whenLoaded('farmer', function () {
                return [
                    'id' => $this->farmer->id,
                    'full_name' => "{$this->farmer->firstname} {$this->farmer->lastname}",
                    'card_identifier' => $this->farmer->card_identifier,
                    'phone' => $this->farmer->phone,
                ];
            }),
            'operator' => $this->whenLoaded('operator', function () {
                return [
                    'id' => $this->operator->id,
                    'username' => $this->operator->username,
                    'role' => $this->operator->role->value,
                ];
            }),
            'subtotal_fcfa' => $this->subtotal_fcfa,
            'payment_method' => $this->payment_method,
            'interest_rate' => $this->interest_rate,
            'interest_amount_fcfa' => $this->interest_amount_fcfa,
            'total_fcfa' => $this->total_fcfa,
            'status' => $this->status,
            'notes' => $this->notes,
            'transacted_at' => $this->transacted_at,
            'items_count' => $this->whenCounted('transactionItems'),
            'has_debt' => $this->whenLoaded('debt', function () {
                return $this->debt !== null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
