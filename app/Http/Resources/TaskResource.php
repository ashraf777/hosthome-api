<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'task_name' => $this->task_name,
            'property_id' => $this->property_id,
            'preset_task_id' => $this->preset_task_id,
            'room_type_id' => $this->room_type_id,
            'unit_id' => $this->unit_id,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date ? $this->due_date->format('Y-m-d') : null,
            'cleaning_team_id' => $this->cleaning_team_id,
            'checklist_id' => $this->checklist_id,
            'num_of_cleaners' => $this->num_of_cleaners,
            'host_notes' => $this->host_notes,
            'remarks' => $this->remarks,
            'created_by_user_id' => $this->created_by_user_id,
            'completed_at' => $this->completed_at ? $this->completed_at->toDateTimeString() : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),

            // Relationships - Eager load these for better performance
            'property' => new PropertyResource($this->whenLoaded('property')),
            'room_type' => new RoomTypeResource($this->whenLoaded('roomType')),
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'cleaning_team' => new CleaningTeamResource($this->whenLoaded('cleaningTeam')),
            'checklist' => new ChecklistResource($this->whenLoaded('checklist')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'logs' => TaskLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
