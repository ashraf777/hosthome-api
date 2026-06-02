<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Multitenant;
use App\Models\User;

class Booking extends Model
{
    use HasFactory, Multitenant;
    protected $guarded = ['id'];

    protected static function booted()
    {
        static::creating(function ($booking) {
            if (empty($booking->guest_portal_token)) {
                $booking->guest_portal_token = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::created(function ($booking) {
            try {
                $adminIds = User::where('hosting_company_id', $booking->hosting_company_id)
                    ->whereHas('role', function ($q) {
                        $q->where('name', '!=', 'Staff/Cleaner');
                    })
                    ->pluck('id')
                    ->toArray();

                if (!empty($adminIds)) {
                    $guestName = $booking->guest ? ($booking->guest->first_name . ' ' . ($booking->guest->last_name ?? '')) : 'Guest';
                    \App\Jobs\SendPushNotification::dispatch(
                        $adminIds,
                        'New Booking Created',
                        "Booking for {$guestName} from " . date('M d', strtotime($booking->check_in_date)) . " to " . date('M d', strtotime($booking->check_out_date)),
                        'booking',
                        $booking->id
                    );
                }
            } catch (\Exception $ex) {
                \Log::error("Failed dispatching new booking notification: " . $ex->getMessage());
            }
        });
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function propertyUnit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function hostingCompany()
    {
        return $this->belongsTo(HostingCompany::class, 'hosting_company_id');
    }

    public function bookingTypeReference()
    {
        return $this->belongsTo(BookingTypeReference::class);
    }

    public function channelReference()
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }
    public function itemsProvided()
    {
        return $this->hasMany(BookingItemProvided::class);
    }

    public function charges()
    {
        return $this->hasMany(BookingCharge::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
