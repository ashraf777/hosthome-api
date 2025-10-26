<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomTypePhoto extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'room_type_photos';

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}