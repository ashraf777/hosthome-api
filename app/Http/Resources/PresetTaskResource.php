<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresetTaskResource extends JsonResource
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
            'preset_task_name' => $this->preset_task_name,
            'property_id' => $this->property_id,
            'room_type_id' => $this->room_type_id,
            'unit_id' => $this->unit_id,
            'trigger_type' => $this->trigger_type,
            'cleaning_team_id' => $this->cleaning_team_id,
            'num_of_cleaners' => $this->num_of_cleaners,
            'checklist_id' => $this->checklist_id,
            'remark' => $this->remark,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),

            // Relationships
            'property' => new PropertyResource($this->whenLoaded('property')),
            'room_type' => new RoomTypeResource($this->whenLoaded('roomType')),
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'cleaning_team' => new CleaningTeamResource($this->whenLoaded('cleaningTeam')),
            'checklist' => new ChecklistResource($this->whenLoaded('checklist')),
        ];
    }
}
