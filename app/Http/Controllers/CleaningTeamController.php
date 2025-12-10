<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CleaningTeam;
use App\Models\User;
use App\Http\Resources\CleaningTeamResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CleaningTeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CleaningTeam::where('hosting_company_id', $request->user()->hosting_company_id)
            ->with(['teamLeader', 'members', 'hostingCompany'])
            ->latest();

        $teams = $query->get();

        return CleaningTeamResource::collection($teams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_name' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
            'team_leader_id' => [
                'nullable',
                'exists:users,id',
                // Ensure the user belongs to the same hosting company
                Rule::exists('users', 'id')->where(function ($query) use ($request) {
                    $query->where('hosting_company_id', $request->user()->hosting_company_id);
                }),
            ],
            'members' => 'sometimes|array',
            'members.*' => [
                'integer',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) use ($request) {
                    $query->where('hosting_company_id', $request->user()->hosting_company_id);
                }),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        $team = CleaningTeam::create([
            'hosting_company_id' => $request->user()->hosting_company_id,
            'team_name' => $validatedData['team_name'],
            'is_active' => $validatedData['is_active'] ?? true,
            'team_leader_id' => $validatedData['team_leader_id'] ?? null,
        ]);

        if (isset($validatedData['members'])) {
            $team->members()->sync($validatedData['members']);
        }

        return new CleaningTeamResource($team->load(['teamLeader', 'members', 'hostingCompany']));
    }

    /**
     * Display the specified resource.
     */
    public function show(CleaningTeam $cleaningTeam)
    {
        if (request()->user()->hosting_company_id !== $cleaningTeam->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        return new CleaningTeamResource($cleaningTeam->load(['teamLeader', 'members', 'hostingCompany']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CleaningTeam $cleaningTeam)
    {
        if ($request->user()->hosting_company_id !== $cleaningTeam->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'team_name' => 'sometimes|required|string|max:255',
            'is_active' => 'sometimes|boolean',
            'team_leader_id' => [
                'nullable',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) use ($request) {
                    $query->where('hosting_company_id', $request->user()->hosting_company_id);
                }),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cleaningTeam->update($validator->validated());

        return new CleaningTeamResource($cleaningTeam->load(['teamLeader', 'members', 'hostingCompany']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CleaningTeam $cleaningTeam)
    {
        if (request()->user()->hosting_company_id !== $cleaningTeam->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }
        
        $cleaningTeam->members()->detach();
        $cleaningTeam->delete();

        return response()->noContent();
    }

    /**
     * Sync members of a cleaning team.
     */
    public function syncMembers(Request $request, CleaningTeam $cleaningTeam)
    {
        if ($request->user()->hosting_company_id !== $cleaningTeam->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'members' => 'required|array',
            'members.*' => [
                'integer',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) use ($request) {
                    $query->where('hosting_company_id', $request->user()->hosting_company_id);
                }),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cleaningTeam->members()->sync($validator->validated()['members']);

        return new CleaningTeamResource($cleaningTeam->load('members'));
    }

}
