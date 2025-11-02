<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyOwnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,  
            'status' => $this->status,  
            'hosting_company_id' => $this->hosting_company_id,            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}