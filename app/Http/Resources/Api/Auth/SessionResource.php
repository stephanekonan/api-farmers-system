<?php

namespace App\Http\Resources\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_name' => $this->device_name,
            'ip_address' => $this->ip_address,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at,
            'expires_at' => $this->expires_at,
        ];
    }
}
