<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingItemProvided extends Model
{
    use HasFactory;

    protected $table = 'booking_items_provided';
    protected $guarded = ['id'];
    public $timestamps = false;
}
