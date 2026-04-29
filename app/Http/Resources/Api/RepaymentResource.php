<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class RepaymentResource extends JsonResource
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

            'commodity_kg' => $this->commodity_kg,
            'commodity_rate_fcfa_per_kg' => $this->commodity_rate_fcfa_per_kg,
            'total_fcfa_value' => $this->total_fcfa_value,

            'notes' => $this->notes,
            'repaid_at' => $this->repaid_at,

            'debts_applied' => $this->whenLoaded(
                'debts',
                fn() =>
                $this->debts->map(fn($debt) => [
                    'debt' => new DebtMiniResource($debt),
                    'amount_applied_fcfa' => $debt->pivot->amount_applied_fcfa,
                ])
            ),
        ];
    }
}