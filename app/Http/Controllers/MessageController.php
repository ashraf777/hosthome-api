<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\GuestMessageMail;

class MessageController extends Controller
{
    /**
     * Display a listing of messages grouped by booking (Inbox View).
     */
    public function index(Request $request)
    {
        if (!$request->user()->canPermission('message:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // For the inbox, we usually want to fetch the latest messages per booking
        // Or fetch bookings that have messages, along with their latest message.
        // For simplicity, let's load bookings with their messages.
        
        $bookings = Booking::with(['guest', 'property', 'messages' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])
        ->whereHas('messages')
        ->orderByDesc(
            Message::select('created_at')
                ->whereColumn('booking_id', 'bookings.id')
                ->orderByDesc('created_at')
                ->limit(1)
        )
        ->paginate(20);

        return response()->json($bookings);
    }

    /**
     * Fetch the message history for a specific booking.
     */
    public function thread(Request $request, Booking $booking)
    {
        if (!$request->user()->canPermission('message:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $messages = $booking->messages()->orderBy('created_at', 'asc')->get();
        return response()->json([
            'booking' => $booking->load(['guest', 'property']),
            'messages' => $messages
        ]);
    }

    /**
     * Send a new manual message from the Host to the Guest.
     */
    public function store(Request $request, Booking $booking)
    {
        if (!$request->user()->canPermission('message:create')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $request->validate([
            'content' => 'required|string',
            'channel' => 'required|string|in:beds24,email,whatsapp,magic-link',
        ]);

        // 1. Create the local message record
        $message = Message::create([
            'booking_id' => $booking->id,
            'guest_id' => $booking->guest_id,
            'direction' => 'outbound',
            'channel' => $request->channel,
            'content' => $request->content,
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        // 2. Dispatch the message via the appropriate channel
        if ($request->channel === 'beds24') {
            $success = app(\App\Services\Beds24Service::class)->sendMessage($message);
            if (!$success) {
                $message->update(['status' => 'failed']);
                return response()->json(['error' => 'Failed to relay message to Beds24'], 500);
            }
        } else {
            // Email/WhatsApp Logic
            if ($request->channel === 'email') {
                try {
                    Mail::to($booking->guest->email)->send(new GuestMessageMail($message));
                    $message->update(['status' => 'delivered']);
                } catch (\Exception $e) {
                    \Log::error("Email Sending Error: " . $e->getMessage());
                    $message->update(['status' => 'failed']);
                    return response()->json(['error' => 'Failed to send email: ' . $e->getMessage()], 500);
                }
            } else {
                // TODO: WhatsApp
                $message->update(['status' => 'delivered']);
            }
        }

        return response()->json($message, 201);
    }
}
