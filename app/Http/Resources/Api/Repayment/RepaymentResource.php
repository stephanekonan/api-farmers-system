<?php

namespace App\Http\Resources\Api\Repayment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepaymentResource extends JsonResource
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
            'commodity_kg' => $this->commodity_kg,
            'commodity_rate_fcfa_per_kg' => $this->commodity_rate_fcfa_per_kg,
            'total_fcfa_value' => $this->total_fcfa_value,
            'notes' => $this->notes,
            'repaid_at' => $this->repaid_at,
            'debts_count' => $this->whenCounted('debts'),
            'total_applied_to_debts' => $this->whenLoaded('debts', function () {
                return $this->debts->sum('pivot.amount_applied_fcfa');
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
