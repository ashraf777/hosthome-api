<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CleaningTeamResource extends JsonResource
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
            'team_name' => $this->team_name,
            'is_active' => $this->is_active,
            'team_leader_id' => $this->team_leader_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),

            // Eager-loaded relationships
            'team_leader' => new UserResource($this->whenLoaded('teamLeader')),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'hosting_company' => new HostingCompanyResource($this->whenLoaded('hostingCompany')),
        ];
    }
}
