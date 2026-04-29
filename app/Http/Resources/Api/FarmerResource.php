<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class FarmerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'card_identifier' => $this->card_identifier,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'phone' => $this->phone,
            'village' => $this->village,
            'region' => $this->region,
        ];
    }
}