<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingRuleResource extends JsonResource
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
            'room_type_id' => $this->room_type_id,
            'name' => $this->name,
            'rule_type' => $this->rule_type,
            'price_modifier' => $this->price_modifier,
            'modifier_type' => $this->modifier_type,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'day_of_week' => $this->day_of_week,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
