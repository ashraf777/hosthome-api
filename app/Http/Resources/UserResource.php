<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => (int) $this->status,
            'hosting_company_id' => $this->hosting_company_id,
            'created_at' => $this->created_at,

            // CRITICAL: Include the user's role for frontend authorization checks
            'role' => [
                'id' => $this->role_id,
                'name' => optional($this->role)->name,
            ],
            
            // NOTE: Do NOT expose the 'password' or raw 'access_token' here.
        ];
    }
}
