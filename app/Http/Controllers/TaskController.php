<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskLogResource;
use App\Jobs\SendCleanerPushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\TaskLog;

class TaskController extends Controller
{
    function getStatusValue($status) {
        switch ($status) {
            case 'To Do':
                return 0;
            case 'In Progress':
                return 1;
            case 'Paused':
                return 2;
            case 'Completed':
                return 3;
            case 'Cancelled':
                return 4;
            default:
                return -1; // Default case
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$request->user()->canPermission('task:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Basic filtering example (can be expanded)
        $tasks = Task::with(['property', 'roomType', 'unit', 'cleaningTeam', 'checklist', 'creator'])
            ->where('hosting_company_id', $request->user()->hosting_company_id)
            ->latest()
            ->paginate(20);

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->canPermission('task:create')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'task_name' => 'required|string|max:255',
            'property_id' => 'nullable',
            'preset_task_id' => 'nullable',
            'room_type_id' => 'nullable',
            'unit_id' => 'nullable',
            'status' => 'required|in:To Do,In Progress,Paused,Completed,Cancelled',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'due_date' => 'nullable|date',
            'cleaning_team_id' => 'nullable|exists:cleaning_teams,id',
            'checklist_id' => 'nullable|exists:checklists,id',
            'num_of_cleaners' => 'sometimes|integer|min:1',
            'host_notes' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $task = Task::create(array_merge($validatedData, [
            'hosting_company_id' => $request->user()->hosting_company_id,
            'created_by_user_id' => $request->user()->id,
        ]));

        // Log the creation event
        TaskLog::create([
            'task_id'   => $task->id,
            'user_id'   => $request->user()->id,
            'status'    => $this->getStatusValue($task->status),
            'log_entry' => 'Task created.',
        ]);

        // Dispatch push notification to all team members (if a team is assigned)
        if ($task->cleaning_team_id) {
            $teamMemberIds = \App\Models\CleaningTeam::with('members')
                ->find($task->cleaning_team_id)
                ?->members->pluck('id')->toArray() ?? [];

            if (!empty($teamMemberIds)) {
                SendCleanerPushNotification::dispatch(
                    $teamMemberIds,
                    'New Task Assigned',
                    "A new task '{$task->task_name}' has been assigned to your team.",
                    'new_task',
                    $task->id,
                );
            }
        }

        return new TaskResource($task->load(['property', 'roomType', 'unit', 'cleaningTeam', 'checklist', 'creator']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {
        if (!$request->user()->canPermission('task:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Basic authorization check
        if ($request->user()->hosting_company_id !== $task->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        return new TaskResource($task->load(['property', 'roomType', 'unit', 'cleaningTeam', 'checklist', 'creator', 'logs']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        if (!$request->user()->canPermission('task:update')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Basic authorization check
        if ($request->user()->hosting_company_id !== $task->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'task_name' => 'sometimes|required|string|max:255',
            'property_id' => 'nullable',
            'preset_task_id' => 'nullable',
            'room_type_id' => 'nullable',
            'unit_id' => 'nullable',
            'status' => 'sometimes|required|in:To Do,In Progress,Paused,Completed,Cancelled',
            'priority' => 'sometimes|required|in:Low,Medium,High,Urgent',
            'due_date' => 'nullable|date',
            'cleaning_team_id' => 'nullable|exists:cleaning_teams,id',
            'checklist_id' => 'nullable|exists:checklists,id',
            'num_of_cleaners' => 'sometimes|integer|min:1',
            'host_notes' => 'nullable|string',
            'remarks' => 'nullable|string',
            'completed_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $originalTaskData = $task->toArray();
        $validatedData = $validator->validated();

        $task->update($validatedData);

        $changes = array_diff_assoc($task->getAttributes(), $originalTaskData);
        unset($changes['updated_at']);

        if (!empty($changes)) {
            $logEntries = [];
            foreach ($changes as $field => $newValue) {
                $oldValue = $originalTaskData[$field] ?? 'null';
                $logEntries[] = "Updated '{$field}' from '{$oldValue}' to '{$newValue}'.";
            }
            
            TaskLog::create([
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'status' => $this->getStatusValue($task->status),
                'log_entry' => implode("\n", $logEntries)
            ]);
        }

        return new TaskResource($task->load(['property', 'roomType', 'unit', 'cleaningTeam', 'checklist', 'creator']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task)
    {
        if (!$request->user()->canPermission('task:delete')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Basic authorization check
        if ($request->user()->hosting_company_id !== $task->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        TaskLog::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'status' => 4, // Custom status for 'deleted'
            'log_entry' => 'Task deleted.'
        ]);
        
        $task->delete();

        return response()->noContent();
    }

    /**
     * Get the logs for a specific task.
     */
    public function getLogs(Request $request, Task $task)
    {
        if (!$request->user()->canPermission('task:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        if ($request->user()->hosting_company_id !== $task->hosting_company_id) {
            abort(403, 'Unauthorized action.');
        }

        $logs = $task->logs()->with('user')->latest()->paginate(20);

        return TaskLogResource::collection($logs);
    }
}
