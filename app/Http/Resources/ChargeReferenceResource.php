<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChargeReferenceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'hosting_company_id' => $this->hosting_company_id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
