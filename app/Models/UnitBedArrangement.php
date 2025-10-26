<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitBedArrangement extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'unit_bed_arrangements';
    protected $casts = ['bed_details' => 'array'];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}