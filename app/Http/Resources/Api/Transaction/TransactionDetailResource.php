<?php

namespace App\Http\Resources\Api\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionDetailResource extends JsonResource
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
                    'village' => $this->farmer->village,
                    'region' => $this->farmer->region,
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
            'transaction_items' => $this->whenLoaded('transactionItems', function () {
                return $this->transactionItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => [
                            'id' => $item->product->id,
                            'product_name' => $item->product->product_name,
                            'unit' => $item->product->unit,
                        ],
                        'product_name' => $item->product_name,
                        'unit_price_fcfa' => $item->unit_price_fcfa,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'line_total_fcfa' => $item->line_total_fcfa,
                    ];
                });
            }),
            'debt' => $this->whenLoaded('debt', function () {
                return $this->debt ? [
                    'id' => $this->debt->id,
                    'original_amount_fcfa' => $this->debt->original_amount_fcfa,
                    'paid_amount_fcfa' => $this->debt->paid_amount_fcfa,
                    'remaining_amount_fcfa' => $this->debt->remaining_amount_fcfa,
                    'status' => $this->debt->status,
                    'incurred_at' => $this->debt->incurred_at,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
