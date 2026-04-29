<?php

namespace App\Http\Resources\Api\Farmer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'card_identifier' => $this->card_identifier,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'phone' => $this->phone,
            'village' => $this->village,
            'region' => $this->region,
            'credit_limit_fcfa' => $this->credit_limit_fcfa,
            'total_outstanding_debt' => $this->total_outstanding_debt,
            'available_credit' => $this->credit_limit_fcfa - $this->total_outstanding_debt,
            'is_active' => $this->is_active,
            'credit_utilization_percentage' => $this->credit_limit_fcfa > 0 
                ? round(($this->total_outstanding_debt / $this->credit_limit_fcfa) * 100, 2)
                : 0,
            'is_exceeding_credit_limit' => $this->total_outstanding_debt > $this->credit_limit_fcfa,
            'has_debt' => $this->total_outstanding_debt > 0,
            'full_name' => "{$this->firstname} {$this->lastname}",
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
