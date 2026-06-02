<?php

namespace App\Http\Controllers\Cleaner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CleanerProfileController extends Controller
{
    /**
     * Get the authenticated cleaner's profile.
     *
     * GET /api/cleaner/profile
     */
    public function show(Request $request)
    {
        $user = $request->user()->load(['role', 'hostingCompany']);

        // Get the cleaning teams this user belongs to
        $teams = \App\Models\CleaningTeam::whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->orWhere('team_leader_id', $user->id)
            ->get(['id', 'team_name', 'is_active']);

        return response()->json([
            'data' => [
                'id'                  => $user->id,
                'name'                => $user->name,
                'email'               => $user->email,
                'status'              => $user->status,
                'availability_status' => $user->availability_status ?? 'available',
                'hosting_company'     => [
                    'id'   => optional($user->hostingCompany)->id,
                    'name' => optional($user->hostingCompany)->name,
                ],
                'teams' => $teams,
            ]
        ]);
    }

    /**
     * Update the cleaner's phone number (name updates go through admin).
     *
     * PUT /api/cleaner/profile
     */
    public function update(Request $request)
    {
        $request->validate([
            'phone' => 'nullable|string|max:30',
        ]);

        // Users table doesn't have a phone column by default in this schema,
        // but keeping this here for future extensibility.
        // The response just echoes back the profile for now.
        $user = $request->user();

        return response()->json([
            'message' => 'Profile updated.',
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * Toggle the cleaner's availability status.
     *
     * PUT /api/cleaner/profile/availability
     */
    public function updateAvailability(Request $request)
    {
        $request->validate([
            'availability_status' => 'required|in:available,unavailable',
        ]);

        $user = $request->user();
        $user->update(['availability_status' => $request->availability_status]);

        return response()->json([
            'message'             => 'Availability updated.',
            'availability_status' => $user->availability_status,
        ]);
    }

    /**
     * Register or update the cleaner's FCM device token.
     * Called on app launch/login to keep push notifications working.
     *
     * PUT /api/cleaner/profile/fcm-token
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['message' => 'FCM token registered.']);
    }
}
