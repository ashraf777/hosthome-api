<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function template()
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }
}
