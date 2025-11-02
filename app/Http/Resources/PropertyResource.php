<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
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
            'property_owner_id' => $this->property_owner_id,
            'name' => $this->name,
            'address_line_1' => $this->address_line_1,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'listing_status' => $this->listing_status,
            'status' => $this->status,
            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'min_nights' => $this->min_nights,
            'max_nights' => $this->max_nights,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships (loaded conditionally)
            'owner' => new PropertyOwnerResource($this->whenLoaded('owner')),
            'hosting_company' => new HostingCompanyResource($this->whenLoaded('hostingCompany')),
            'property_type' => new PropertyReferenceResource($this->whenLoaded('propertyType')),
            'room_types' => RoomTypeResource::collection($this->whenLoaded('roomTypes')),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
        ];
    }
}
