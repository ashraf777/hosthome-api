<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Multitenant;

class Guest extends Model
{
    use HasFactory, Multitenant;
    protected $guarded = ['id'];

    public function vehicles()
    {
        return $this->hasMany(GuestVehicle::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
