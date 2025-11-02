<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmenityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amenities_reference_id' => $this->amenities_reference_id,
            'specific_name' => $this->specific_name,
            'status' => $this->status,
            'amenity_reference' => new AmenityReferenceResource($this->whenLoaded('amenityReference')),
        ];
    }
}
