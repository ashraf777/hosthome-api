<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use App\Models\User;

class Message extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::created(function ($message) {
            if ($message->direction === 'inbound') {
                try {
                    $booking = $message->booking;
                    if ($booking) {
                        $adminIds = User::where('hosting_company_id', $booking->hosting_company_id)
                            ->whereHas('role', function ($q) {
                                $q->where('name', '!=', 'Staff/Cleaner');
                            })
                            ->pluck('id')
                            ->toArray();

                        if (!empty($adminIds)) {
                            $guestName = $message->guest ? ($message->guest->first_name . ' ' . ($message->guest->last_name ?? '')) : 'Guest';
                            \App\Jobs\SendPushNotification::dispatch(
                                $adminIds,
                                'New Message from ' . $guestName,
                                Str::limit($message->content, 120),
                                'message',
                                $booking->id
                            );
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed dispatching incoming message notification: " . $e->getMessage());
                }
            }
        });
    }

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
