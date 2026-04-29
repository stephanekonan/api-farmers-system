<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,

            'farmer' => new FarmerResource($this->whenLoaded('farmer')),

            'transaction' => $this->whenLoaded('transaction', fn() => [
                'id' => $this->transaction->id,
                'reference' => $this->transaction->reference,
                'total_fcfa' => $this->transaction->total_fcfa,
                'transacted_at' => $this->transaction->transacted_at,
            ]),

            'original_amount_fcfa' => $this->original_amount_fcfa,
            'paid_amount_fcfa' => $this->paid_amount_fcfa,
            'remaining_amount_fcfa' => $this->remaining_amount_fcfa,
            'status' => $this->status,

            'repayments' => $this->whenLoaded(
                'repayments',
                fn() =>
                $this->repayments->map(fn($repayment) => [
                    'id' => $repayment->id,
                    'reference' => $repayment->reference,
                    'amount_applied_fcfa' => $repayment->pivot->amount_applied_fcfa,
                    'repaid_at' => $repayment->repaid_at,
                ])
            ),
        ];
    }
}