<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomTypeResource extends JsonResource
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
            'hosting_company_id' => $this->hosting_company_id,
            'property_id' => $this->property_id,
            'name' => $this->name,
            'max_adults' => $this->max_adults,
            'max_children' => $this->max_children,
            'size' => $this->size,
            'weekday_price' => $this->weekday_price,
            'weekend_price' => $this->weekend_price,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships (loaded conditionally)
            'hosting_company' => new HostingCompanyResource($this->whenLoaded('hostingCompany')),
            'property' => new PropertyResource($this->whenLoaded('property')),
            'properties' => PropertyResource::collection($this->whenLoaded('properties')),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
            'photos' => RoomTypePhotoResource::collection($this->whenLoaded('photos')),
        ];
    }
}
