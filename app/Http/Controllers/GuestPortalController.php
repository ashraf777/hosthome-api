<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Message;
use Illuminate\Http\Request;

class GuestPortalController extends Controller
{
    /**
     * Get booking details safely using the secure UUID token.
     */
    public function summary($token)
    {
        $booking = Booking::with(['guest', 'property.photos'])
            ->where('guest_portal_token', $token)
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Invalid or expired booking link.'], 404);
        }

        // Return only safe details (no prices, hidden fields, etc)
        return response()->json([
            'id' => $booking->id,
            'check_in_date' => $booking->check_in_date,
            'check_out_date' => $booking->check_out_date,
            'status' => $booking->status,
            'guest' => [
                'first_name' => $booking->guest->first_name,
                'last_name' => $booking->guest->last_name,
            ],
            'property' => [
                'name' => $booking->property->name,
                'address_line_1' => $booking->property->address_line_1,
                'city' => $booking->property->city,
                'state' => $booking->property->state,
                'postcode' => $booking->property->zip_code, // Use zip_code from database
            ]
        ]);
    }

    /**
     * Get the conversation thread for a booking securely.
     */
    public function messages($token)
    {
        $booking = Booking::where('guest_portal_token', $token)->first();

        if (!$booking) {
            return response()->json(['error' => 'Invalid portal link.'], 404);
        }

        $messages = $booking->messages()->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    /**
     * Guest sends a message back to the Host.
     */
    public function sendMessage(Request $request, $token)
    {
        $request->validate([
            'content' => 'required|string|max:2000'
        ]);

        $booking = Booking::where('guest_portal_token', $token)->first();

        if (!$booking) {
            return response()->json(['error' => 'Invalid portal link.'], 404);
        }

        // Create the inbound message from guest to host
        $message = Message::create([
            'booking_id' => $booking->id,
            'guest_id' => $booking->guest_id,
            'direction' => 'inbound',
            'channel' => 'magic-link',
            'content' => $request->content,
            'status' => 'delivered',
            'sent_at' => now(),
        ]);

        return response()->json($message, 201);
    }
}
