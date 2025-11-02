<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'property_unit_id' => $this->property_unit_id,
            'guest_id' => $this->guest_id,
            'owner_statement_id' => $this->owner_statement_id,
            'channel_source' => $this->channel_source,
            'external_reservation_id' => $this->external_reservation_id,
            'channel_booking_id' => $this->channel_booking_id,
            'check_in_date' => $this->check_in_date,
            'check_out_date' => $this->check_out_date,
            'total_price' => $this->total_price,
            'guest_count' => $this->guest_count,
            'adult_count' => $this->adult_count,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'guest' => new GuestResource($this->whenLoaded('guest')),
        ];
    }
}
