<?php

namespace App\Http\Resources\Api\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'full_name' => "{$this->firstname} {$this->lastname}",
            'role' => $this->role,
            'is_active' => $this->is_active,
            'subordinates_count' => $this->whenCounted('subordinates'),
        ];
    }
}
