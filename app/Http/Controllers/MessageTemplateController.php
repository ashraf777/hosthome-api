<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(MessageTemplate::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_event' => 'required|string|in:manual,pre-check-in,post-check-out,booking-confirmed',
            'offset_hours' => 'required|integer',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template = MessageTemplate::create($validated);

        return response()->json($template, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MessageTemplate $messageTemplate)
    {
        return response()->json($messageTemplate);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MessageTemplate $messageTemplate)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'trigger_event' => 'sometimes|string|in:manual,pre-check-in,post-check-out,booking-confirmed',
            'offset_hours' => 'sometimes|integer',
            'subject' => 'nullable|string|max:255',
            'body' => 'sometimes|string',
            'is_active' => 'boolean',
        ]);

        $messageTemplate->update($validated);

        return response()->json($messageTemplate);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MessageTemplate $messageTemplate)
    {
        $messageTemplate->delete();
        return response()->json(null, 204);
    }

    /**
     * Manually trigger the scheduled message dispatch job.
     */
    public function runAutomations()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('messages:send-automated');
            $output = \Illuminate\Support\Facades\Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Automations executed successfully.',
                'log' => trim($output)
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Manual Automation Trigger Failed: " . $e->getMessage());
            return response()->json(['error' => 'Failed to run automations.'], 500);
        }
    }
}
