<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelMapping extends Model
{
    use HasFactory;

    protected $table = 'channels_mapping';

    protected $fillable = [
        'property_unit_id',
        'channel_id',
        'external_unit_id',
        'status',
    ];

    public function propertyUnit()
    {
        return $this->belongsTo(Unit::class, 'property_unit_id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
