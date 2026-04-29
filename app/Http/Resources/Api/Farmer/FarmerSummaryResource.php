<?php

namespace App\Http\Resources\Api\Farmer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmerSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'card_identifier' => $this->card_identifier,
            'full_name' => "{$this->firstname} {$this->lastname}",
            'phone' => $this->phone,
            'village' => $this->village,
            'region' => $this->region,
            'total_outstanding_debt' => $this->total_outstanding_debt,
            'available_credit' => $this->credit_limit_fcfa - $this->total_outstanding_debt,
            'is_active' => $this->is_active,
            'has_debt' => $this->total_outstanding_debt > 0,
            'is_exceeding_credit_limit' => $this->total_outstanding_debt > $this->credit_limit_fcfa,
        ];
    }
}
