<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingCharge extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public $timestamps = false;

    public function chargeReference()
    {
        // Assuming the ChargeReference model is in App\Models\ChargeReference
        return $this->belongsTo(ChargeReference::class, 'charge_reference_id');
    }
}
