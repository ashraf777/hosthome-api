<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_unit_id',
        'guest_id',
        'owner_statement_id',
        'channel_source',
        'external_reservation_id',
        'channel_booking_id',
        'check_in_date',
        'check_out_date',
        'total_price',
        'guest_count',
        'adult_count',
        'status',
    ];

    protected $casts = [
        'check_in_date' => 'datetime',
        'check_out_date' => 'datetime',
    ];

    public function propertyUnit()
    {
        return $this->belongsTo(PropertyUnit::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function ownerStatement()
    {
        return $this->belongsTo(OwnerStatement::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
