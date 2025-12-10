<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PresetTask;
use App\Http\Resources\PresetTaskResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PresetTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $presets = PresetTask::where('hosting_company_id', $request->user()->hosting_company_id)
            ->with(['property', 'roomType', 'unit', 'cleaningTeam', 'checklist']) // Eager load relationships
            ->latest()
            ->paginate(20);

        return PresetTaskResource::collection($presets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'preset_task_name' => 'required|string|max:255',
            'property_id' => 'nullable',
            'room_type_id' => 'nullable',
            'unit_id' => 'nullable',
            'trigger_type' => 'required|string', // Example trigger types
            'cleaning_team_id' => 'nullable',
            'num_of_cleaners' => 'nullable|integer|min:1',
            'checklist_id' => 'nullable',
            'remark' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $validatedData['hosting_company_id'] = $request->user()->hosting_company_id;

        $presetTask = PresetTask::create($validatedData);

        return new PresetTaskResource($presetTask->load(['property', 'roomType', 'unit', 'cleaningTeam', 'checklist']));
    }

    /**
     * Display the specified resource.
     */
    public function show(PresetTask $presetTask)
    {
        if (request()->user()->hosting_company_id !== $presetTask->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        return new PresetTaskResource($presetTask->load(['property', 'roomType', 'unit', 'cleaningTeam', 'checklist']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PresetTask $presetTask)
    {
        if ($request->user()->hosting_company_id !== $presetTask->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'preset_task_name' => 'sometimes|required|string|max:255',
            'property_id' => 'nullable',
            'room_type_id' => 'nullable',
            'unit_id' => 'nullable',
            'trigger_type' => 'sometimes|required|string',
            'cleaning_team_id' => 'nullable',
            'num_of_cleaners' => 'nullable|integer|min:1',
            'checklist_id' => 'nullable',
            'remark' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $presetTask->update($validator->validated());

        return new PresetTaskResource($presetTask->load(['property', 'roomType', 'unit', 'cleaningTeam', 'checklist']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PresetTask $presetTask)
    {
        if (request()->user()->hosting_company_id !== $presetTask->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        $presetTask->delete();

        return response()->noContent();
    }
}
