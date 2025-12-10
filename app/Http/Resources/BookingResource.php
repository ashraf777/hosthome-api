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
            'guest' => new GuestResource($this->whenLoaded('guest')),
            'property' => new PropertyResource($this->whenLoaded('property')),
            'room_type' => new RoomTypeResource($this->whenLoaded('roomType')),
            'property_unit' => new UnitResource($this->whenLoaded('propertyUnit')),
            'hosting_company' => new HostingCompanyResource($this->whenLoaded('hostingCompany')),
            'booking_type' => new BookingTypeReferenceResource($this->whenLoaded('bookingTypeReference')),
            'channel' => new ChannelResource($this->whenLoaded('channelReference')),
            'items_provided' => BookingItemProvidedResource::collection($this->whenLoaded('itemsProvided')),
            'charges' => BookingChargeResource::collection($this->whenLoaded('charges')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'confirmation_code' => $this->confirmation_code,
            'check_in_date' => $this->check_in_date,
            'check_out_date' => $this->check_out_date,
            'number_of_guests' => $this->number_of_guests,
            'raw_room_rate' => $this->raw_room_rate,
            'room_rate_modifier' => $this->room_rate_modifier,
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'amount_due' => $this->amount_due,
            'deposit_not_collected' => $this->deposit_not_collected,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
