<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'name',
        'rule_type',
        'price_modifier',
        'modifier_type',
        'start_date',
        'end_date',
        'day_of_week',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
}
