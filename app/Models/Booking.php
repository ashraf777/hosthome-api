<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected static function booted()
    {
        static::creating(function ($booking) {
            if (empty($booking->guest_portal_token)) {
                $booking->guest_portal_token = (string) \Illuminate\Support\Str::uuid();
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
