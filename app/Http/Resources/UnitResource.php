<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
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
            'property_id' => $this->property_id, // <-- ADDED
            'room_type_id' => $this->room_type_id,
            'unit_type_ref_id' => $this->unit_type_ref_id,
            'unit_identifier' => $this->unit_identifier,
            'status' => $this->status,
            'description' => $this->description,
            'about' => $this->about,
            'guest_access' => $this->guest_access,
            'owner_user_id' => $this->owner_user_id,
            'max_free_stay_days' => $this->max_free_stay_days,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships (loaded conditionally)
            'property' => new PropertyResource($this->whenLoaded('property')), // <-- ADDED
            'room_type' => new RoomTypeResource($this->whenLoaded('roomType')),
            'unit_type_ref' => new PropertyUnitReferenceResource($this->whenLoaded('unitTypeRef')),
            'owner' => new UserResource($this->whenLoaded('owner')),
        ];
    }
}
