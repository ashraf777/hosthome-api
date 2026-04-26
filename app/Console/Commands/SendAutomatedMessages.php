<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MessageTemplate;
use App\Models\Booking;
use App\Models\Message;
use App\Services\MessageVariableService;
use App\Services\Beds24Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\GuestMessageMail;

class SendAutomatedMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messages:send-automated';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan bookings and send automated messages based on active templates and time offsets.';

    /**
     * Execute the console command.
     */
    public function handle(MessageVariableService $variableParser, Beds24Service $beds24Service)
    {
        $templates = MessageTemplate::where('is_active', true)->where('trigger_event', '!=', 'manual')->get();

        if ($templates->isEmpty()) {
            $this->info("No active automated templates found.");
            return;
        }

        $now = Carbon::now();
        $sentCount = 0;

        foreach ($templates as $template) {
            $query = Booking::whereIn('booking_status', ['confirmed']);
            
            // Determine the target time based on the event and offset
            // Typically, offset_hours is negative for "before" and positive for "after"
            // Let's assume offset_hours = 24 means "24 hours before check-in" if trigger_event = pre-check-in
            // We should use an exact hour match window. For instance, bookings checking in exactly `offset_hours` from now.
            // A safer approach: bookings between offset_hours and offset_hours - 1 from now.
            
            if ($template->trigger_event === 'pre-check-in') {
                $targetStart = clone $now;
                $targetStart->addHours($template->offset_hours);
                $targetEnd = clone $targetStart;
                $targetEnd->addHour();

                $query->whereBetween('check_in_date', [$targetStart->toDateString(), $targetEnd->toDateString()]);
                // Need fine-grained time if check_in_time exists, else assume 14:00 check-in
            } elseif ($template->trigger_event === 'post-check-out') {
                $targetStart = clone $now;
                $targetStart->subHours($template->offset_hours);
                $targetEnd = clone $targetStart;
                $targetEnd->subHour();
                $query->whereBetween('check_out_date', [$targetStart->toDateString(), $targetEnd->toDateString()]);
            } elseif ($template->trigger_event === 'booking-confirmed') {
                // Bookings confirmed in the last hour
                $query->where('created_at', '>=', $now->copy()->subHour());
            }

            $bookings = $query->get();

            foreach ($bookings as $booking) {
                // To prevent duplicate sends, check if this template was already sent to this booking
                $alreadySent = Message::where('booking_id', $booking->id)
                    ->where('message_template_id', $template->id)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                // Parse standard body
                $parsedBody = $variableParser->parse($template->body, $booking);

                // Create Message Record
                $isChannel = $booking->external_reservation_id != null;

                $message = Message::create([
                    'booking_id' => $booking->id,
                    'guest_id' => $booking->guest_id,
                    'message_template_id' => $template->id,
                    'direction' => 'outbound',
                    'channel' => $isChannel ? 'beds24' : 'email',
                    'content' => $parsedBody,
                    'status' => 'pending',
                    'sent_at' => now(),
                ]);

                // Dispatch
                if ($isChannel) {
                    $success = $beds24Service->sendMessage($message);
                    if (!$success) {
                        $message->update(['status' => 'failed']);
                    } else {
                        $sentCount++;
                    }
                } else {
                    // Direct Booking (Email or WhatsApp fallback)
                    try {
                        Mail::to($booking->guest->email)->send(new GuestMessageMail($message));
                        $message->update(['status' => 'delivered']);
                        $sentCount++;
                    } catch (\Exception $e) {
                        Log::error("Automated Email Error: " . $e->getMessage());
                        $message->update(['status' => 'failed']);
                    }
                }
            }
        }

        $this->info("Automated messages run complete. Sent: {$sentCount}");
    }
}
