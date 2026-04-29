<?php

namespace App\Http\Resources\Api\Debt;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'farmer' => $this->whenLoaded('farmer', function () {
                return [
                    'id' => $this->farmer->id,
                    'full_name' => "{$this->farmer->firstname} {$this->farmer->lastname}",
                    'card_identifier' => $this->farmer->card_identifier,
                    'phone' => $this->farmer->phone,
                ];
            }),
            'transaction' => $this->whenLoaded('transaction', function () {
                return $this->transaction ? [
                    'id' => $this->transaction->id,
                    'reference' => $this->transaction->reference,
                    'transacted_at' => $this->transaction->transacted_at,
                ] : null;
            }),
            'original_amount_fcfa' => $this->original_amount_fcfa,
            'paid_amount_fcfa' => $this->paid_amount_fcfa,
            'remaining_amount_fcfa' => $this->remaining_amount_fcfa,
            'status' => $this->status,
            'incurred_at' => $this->incurred_at,
            'fully_paid_at' => $this->fully_paid_at,
            'payment_percentage' => $this->original_amount_fcfa > 0 
                ? round(($this->paid_amount_fcfa / $this->original_amount_fcfa) * 100, 2)
                : 0,
            'is_overdue' => $this->status === 'pending' && 
                           $this->incurred_at->lt(now()->subDays(30)),
            'repayments_count' => $this->whenCounted('repayments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
