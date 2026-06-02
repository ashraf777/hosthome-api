<?php

namespace App\Http\Controllers\Cleaner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CleanerAuthController extends Controller
{
    /**
     * Step 1: Cleaner requests a PIN by entering their email.
     * Backend generates a 4-digit PIN, caches plain text for admin to view,
     * stores hashed version in users.login_pin, and sends email to admin.
     *
     * POST /api/cleaner/auth/request-pin
     */
    public function requestPin(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::with('role')
            ->where('email', $request->email)
            ->where('status', 1)
            ->first();

        if (!$user || $user->role->name !== 'Staff/Cleaner') {
            // Return a generic message to avoid user enumeration
            return response()->json([
                'message' => 'If this email is registered as a cleaner, a PIN will be sent to the admin.'
            ]);
        }

        // Generate a 4-digit PIN
        $pin = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        // Store hashed PIN + expiry in user record
        $user->update([
            'login_pin'      => Hash::make($pin),
            'pin_expires_at' => now()->addMinutes(10),
        ]);

        // Cache plain PIN for admin panel display (10 min TTL matches PIN expiry)
        Cache::put("cleaner_pin_request_{$user->id}", [
            'user_id'    => $user->id,
            'user_name'  => $user->name,
            'user_email' => $user->email,
            'pin'        => $pin,
            'expires_at' => now()->addMinutes(10)->toISOString(),
        ], now()->addMinutes(10));

        // Send email notification to admin (hosting company contact email)
        $adminEmail = optional($user->hostingCompany)->contact_email;
        if ($adminEmail) {
            try {
                Mail::raw(
                    "Cleaner Login PIN Request\n\n" .
                    "Staff Member: {$user->name} ({$user->email})\n" .
                    "PIN: {$pin}\n" .
                    "This PIN expires in 10 minutes.\n\n" .
                    "Please provide this PIN to the staff member.",
                    function ($message) use ($adminEmail, $user) {
                        $message->to($adminEmail)
                            ->subject("Cleaner App Login PIN — {$user->name}");
                    }
                );
            } catch (\Exception $e) {
                // Log but don't fail — admin can still see PIN in web panel
                \Log::warning('Failed to send PIN email: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'PIN has been sent to your admin. Please ask them for your 4-digit PIN.'
        ]);
    }

    /**
     * Step 2: Cleaner submits their PIN to get an access token.
     *
     * POST /api/cleaner/auth/verify-pin
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'pin'   => 'required|string|min:4|max:4',
        ]);

        $user = User::with('role')
            ->where('email', $request->email)
            ->where('status', 1)
            ->first();

        if (!$user || $user->role->name !== 'Staff/Cleaner') {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (!$user->login_pin || !$user->pin_expires_at) {
            return response()->json(['message' => 'No PIN was requested. Please request a new PIN.'], 400);
        }

        if (now()->isAfter($user->pin_expires_at)) {
            // Clear expired PIN
            $user->update(['login_pin' => null, 'pin_expires_at' => null]);
            return response()->json(['message' => 'PIN has expired. Please request a new PIN.'], 401);
        }

        if (!Hash::check($request->pin, $user->login_pin)) {
            return response()->json(['message' => 'Invalid PIN. Please try again.'], 401);
        }

        // PIN is valid — generate session token and clear PIN fields
        $token = Str::random(64);
        $user->update([
            'access_token'   => $token,
            'login_pin'      => null,
            'pin_expires_at' => null,
        ]);

        // Remove from admin cache
        Cache::forget("cleaner_pin_request_{$user->id}");

        return response()->json([
            'message'      => 'Login successful.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user' => [
                'id'                  => $user->id,
                'name'                => $user->name,
                'email'               => $user->email,
                'availability_status' => $user->availability_status ?? 'available',
            ],
        ]);
    }

    /**
     * Logout: Clears the access token from the database.
     *
     * POST /api/cleaner/auth/logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->update(['access_token' => null, 'fcm_token' => null]);
        }

        return response()->json(['message' => 'Successfully logged out.']);
    }

    /**
     * Admin panel endpoint: Get all pending PIN requests.
     * Protected by the main admin middleware (not cleaner middleware).
     *
     * GET /api/cleaner/auth/pending-pins
     */
    public function pendingPins(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $hostingCompanyId = $request->user()->hosting_company_id;

        // Get all users with the Staff/Cleaner role in this hosting company
        $cleaners = User::with('role')
            ->where('hosting_company_id', $hostingCompanyId)
            ->whereHas('role', fn($q) => $q->where('name', 'Staff/Cleaner'))
            ->whereNotNull('login_pin')
            ->get(['id']);

        $pendingPins = [];
        foreach ($cleaners as $cleaner) {
            $cached = Cache::get("cleaner_pin_request_{$cleaner->id}");
            if ($cached) {
                $pendingPins[] = $cached;
            }
        }

        return response()->json(['data' => $pendingPins]);
    }
}
