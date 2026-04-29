<?php

namespace App\Http\Resources\Api\Debt;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'farmer' => [
                'id' => $this->farmer->id,
                'full_name' => "{$this->farmer->firstname} {$this->farmer->lastname}",
                'card_identifier' => $this->farmer->card_identifier,
            ],
            'original_amount_fcfa' => $this->original_amount_fcfa,
            'remaining_amount_fcfa' => $this->remaining_amount_fcfa,
            'status' => $this->status,
            'incurred_at' => $this->incurred_at,
            'payment_percentage' => $this->original_amount_fcfa > 0 
                ? round(($this->paid_amount_fcfa / $this->original_amount_fcfa) * 100, 2)
                : 0,
            'is_overdue' => $this->status === 'pending' && 
                           $this->incurred_at->lt(now()->subDays(30)),
            'days_overdue' => $this->status === 'pending' 
                ? max(0, now()->diffInDays($this->incurred_at) - 30)
                : 0,
        ];
    }
}
