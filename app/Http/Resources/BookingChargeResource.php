<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ChargeReferenceResource; 
class BookingChargeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            // Fields directly from the booking_charges table
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'charge_reference_id' => $this->charge_reference_id,
            'amount' => $this->amount, 

            // Nest the reference data using your existing resource
            // This relies on the 'chargeReference' relationship defined in Step 1
            'charge_details' => new ChargeReferenceResource($this->whenLoaded('chargeReference')),
        ];
    }
}