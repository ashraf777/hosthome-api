<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $portalToken;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
        $this->portalToken = $booking->guest_portal_token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking Confirmed - ' . $this->booking->property->name
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmation'
        );
    }
}
