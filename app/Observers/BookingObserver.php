<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\Beds24Service;

class BookingObserver
{
    protected $beds24Service;

    public function __construct(Beds24Service $beds24Service)
    {
        $this->beds24Service = $beds24Service;
    }

    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking)
    {
        $this->beds24Service->pushBookingToBeds24($booking);
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking)
    {
        // Only push if key fields changed or status updated
        if ($booking->isDirty(['check_in_date', 'check_out_date', 'status', 'property_unit_id', 'number_of_guests'])) {
            $this->beds24Service->pushBookingToBeds24($booking);
        }
    }

    /**
     * Handle the Booking "deleting" event.
     */
    public function deleting(Booking $booking)
    {
        $this->beds24Service->cancelBookingOnBeds24($booking);
    }
}
