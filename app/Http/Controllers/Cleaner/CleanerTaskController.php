<?php

namespace App\Http\Controllers\Cleaner;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CleanerTaskController extends Controller
{
    /**
     * Get team ID(s) for the authenticated cleaner.
     */
    private function getCleanerTeamIds($user): array
    {
        $memberTeams = \App\Models\CleaningTeam::whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->pluck('id')->toArray();

        $ledTeams = \App\Models\CleaningTeam::where('team_leader_id', $user->id)
            ->pluck('id')->toArray();

        return array_unique(array_merge($memberTeams, $ledTeams));
    }

    private function getStatusIntValue($status): int
    {
        return match ($status) {
            'To Do'      => 0,
            'In Progress' => 1,
            'Paused'     => 2,
            'Completed'  => 3,
            'Cancelled'  => 4,
            default      => -1,
        };
    }

    /**
     * Active tasks for the cleaner's team(s).
     * Shows tasks with status != Completed/Cancelled.
     *
     * GET /api/cleaner/tasks
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $teamIds = $this->getCleanerTeamIds($user);

        if (empty($teamIds)) {
            return response()->json(['data' => [], 'message' => 'You are not assigned to any cleaning team.']);
        }

        $tasks = Task::with(['property', 'unit', 'cleaningTeam', 'checklist.items'])
            ->where('hosting_company_id', $user->hosting_company_id)
            ->whereIn('cleaning_team_id', $teamIds)
            ->whereNotIn('status', ['Completed', 'Cancelled'])
            ->latest('updated_at')
            ->get()
            ->map(fn($task) => $this->formatTask($task));

        return response()->json(['data' => $tasks]);
    }

    /**
     * Completed tasks for the cleaner's team(s) — task history.
     *
     * GET /api/cleaner/tasks/history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $teamIds = $this->getCleanerTeamIds($user);

        if (empty($teamIds)) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        $tasks = Task::with(['property', 'unit', 'cleaningTeam', 'taskMedia'])
            ->where('hosting_company_id', $user->hosting_company_id)
            ->whereIn('cleaning_team_id', $teamIds)
            ->where('status', 'Completed')
            ->latest('completed_at')
            ->paginate(20);

        return response()->json([
            'data' => $tasks->items() ? array_map(fn($t) => $this->formatTask($t), $tasks->items()) : [],
            'meta' => [
                'total'        => $tasks->total(),
                'current_page' => $tasks->currentPage(),
                'last_page'    => $tasks->lastPage(),
            ],
        ]);
    }

    /**
     * Full task detail.
     *
     * GET /api/cleaner/tasks/{task}
     */
    public function show(Request $request, Task $task)
    {
        $user = $request->user();
        $teamIds = $this->getCleanerTeamIds($user);

        if (!in_array($task->cleaning_team_id, $teamIds)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $task->load(['property', 'unit', 'cleaningTeam', 'checklist.items', 'logs', 'taskMedia.uploader']);

        return response()->json(['data' => $this->formatTask($task, detailed: true)]);
    }

    /**
     * Update task status from the cleaner app.
     * Validates allowed status transitions and logs the change.
     *
     * PUT /api/cleaner/tasks/{task}/status
     */
    public function updateStatus(Request $request, Task $task)
    {
        $user = $request->user();
        $teamIds = $this->getCleanerTeamIds($user);

        if (!in_array($task->cleaning_team_id, $teamIds)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status'         => 'required|in:In Progress,Paused,Completed',
            'blocked_reason' => 'required_if:status,Paused|nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus = $task->status;
        $newStatus = $request->status;

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'In Progress' && !$task->accepted_at) {
            $updateData['accepted_at'] = now();
        }

        if ($newStatus === 'Paused') {
            $updateData['blocked_reason'] = $request->blocked_reason;
        }

        if ($newStatus === 'Completed') {
            $updateData['completed_at'] = now();
            $updateData['blocked_reason'] = null;
        }

        $task->update($updateData);

        // Log the status change
        TaskLog::create([
            'task_id'   => $task->id,
            'user_id'   => $user->id,
            'status'    => $this->getStatusIntValue($newStatus),
            'log_entry' => "Status changed from '{$oldStatus}' to '{$newStatus}' by {$user->name} (Cleaner App)."
                . ($newStatus === 'Paused' ? " Reason: {$request->blocked_reason}" : ''),
        ]);

        return response()->json([
            'message' => 'Task status updated.',
            'data'    => $this->formatTask($task->fresh()),
        ]);
    }

    /**
     * Upload proof media (photo + optional note) for a task.
     *
     * POST /api/cleaner/tasks/{task}/media
     */
    public function uploadMedia(Request $request, Task $task)
    {
        $user = $request->user();
        $teamIds = $this->getCleanerTeamIds($user);

        if (!in_array($task->cleaning_team_id, $teamIds)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'media'     => 'required|file|mimes:jpg,jpeg,png,heic,mp4|max:20480', // 20MB max
            'note'      => 'nullable|string|max:1000',
            'status_at_upload' => 'required|in:In Progress,Paused,Completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('media');
        $ext  = $file->getClientOriginalExtension();
        $path = $file->storeAs(
            "task-media/{$task->id}",
            uniqid('proof_', true) . ".{$ext}",
            'public'
        );

        $mediaType = in_array(strtolower($ext), ['mp4']) ? 'video' : 'image';

        $media = TaskMedia::create([
            'task_id'          => $task->id,
            'uploaded_by'      => $user->id,
            'media_path'       => $path,
            'media_type'       => $mediaType,
            'note'             => $request->note,
            'status_at_upload' => $request->status_at_upload,
        ]);

        return response()->json([
            'message' => 'Media uploaded successfully.',
            'data'    => [
                'id'               => $media->id,
                'media_url'        => Storage::url($path),
                'note'             => $media->note,
                'status_at_upload' => $media->status_at_upload,
                'created_at'       => $media->created_at,
            ],
        ], 201);
    }

    /**
     * List all proof media for a task.
     *
     * GET /api/cleaner/tasks/{task}/media
     */
    public function listMedia(Request $request, Task $task)
    {
        $user = $request->user();
        $teamIds = $this->getCleanerTeamIds($user);

        if (!in_array($task->cleaning_team_id, $teamIds)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $media = $task->taskMedia()->with('uploader:id,name')->latest()->get()
            ->map(fn($m) => [
                'id'               => $m->id,
                'media_url'        => Storage::url($m->media_path),
                'media_type'       => $m->media_type,
                'note'             => $m->note,
                'status_at_upload' => $m->status_at_upload,
                'uploaded_by'      => $m->uploader?->name,
                'created_at'       => $m->created_at,
            ]);

        return response()->json(['data' => $media]);
    }

    /**
     * Format a task for API response.
     */
    private function formatTask(Task $task, bool $detailed = false): array
    {
        $data = [
            'id'             => $task->id,
            'task_name'      => $task->task_name,
            'status'         => $task->status,
            'priority'       => $task->priority,
            'due_date'       => $task->due_date,
            'accepted_at'    => $task->accepted_at,
            'completed_at'   => $task->completed_at,
            'blocked_reason' => $task->blocked_reason,
            'host_notes'     => $task->host_notes,
            'property'       => $task->property ? [
                'id'   => $task->property->id,
                'name' => $task->property->name,
                'city' => $task->property->city,
            ] : null,
            'unit'           => $task->unit ? [
                'id'              => $task->unit->id,
                'unit_identifier' => $task->unit->unit_identifier,
            ] : null,
            'cleaning_team'  => $task->cleaningTeam ? [
                'id'        => $task->cleaningTeam->id,
                'team_name' => $task->cleaningTeam->team_name,
            ] : null,
            'created_at'     => $task->created_at,
            'updated_at'     => $task->updated_at,
        ];

        if ($detailed) {
            $data['checklist'] = $task->checklist ? [
                'id'            => $task->checklist->id,
                'checklist_name' => $task->checklist->checklist_name,
                'items'         => $task->checklist->items->map(fn($item) => [
                    'id'               => $item->id,
                    'item_description' => $item->item_description,
                    'item_order'       => $item->item_order,
                ])->sortBy('item_order')->values(),
            ] : null;

            $data['media'] = $task->taskMedia ? $task->taskMedia->map(fn($m) => [
                'id'               => $m->id,
                'media_url'        => Storage::url($m->media_path),
                'media_type'       => $m->media_type,
                'note'             => $m->note,
                'status_at_upload' => $m->status_at_upload,
                'uploaded_by'      => $m->uploader?->name,
                'created_at'       => $m->created_at,
            ]) : [];
        }

        return $data;
    }
}
