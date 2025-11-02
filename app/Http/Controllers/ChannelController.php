<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;
use App\Http\Resources\ChannelResource;
use Illuminate\Validation\Rule;

class ChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // CORRECT: Permission Check
        if (!$request->user()->canPermission('channel:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }
        return ChannelResource::collection(Channel::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->canPermission('channel:create')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:channels',
            'external_system_code' => 'nullable|string|max:255',
            'status' => 'required|integer|in:0,1',
        ]);

        $channel = Channel::create($validated);

        return new ChannelResource($channel);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Channel $channel)
    {
        if (!$request->user()->canPermission('channel:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }
        return new ChannelResource($channel);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Channel $channel)
    {
        if (!$request->user()->canPermission('channel:update')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('channels')->ignore($channel->id)],
            'external_system_code' => 'nullable|string|max:255',
            'status' => 'sometimes|required|integer|in:0,1',
        ]);

        $channel->update($validated);

        return new ChannelResource($channel);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Channel $channel)
    {
        if (!$request->user()->canPermission('channel:delete')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $channel->delete();

        return response()->noContent();
    }
}
