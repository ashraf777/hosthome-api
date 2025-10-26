<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    // Relationships
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
    
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
    
    public function amenities(): BelongsToMany // Many-to-Many via pivot table
    {
        return $this->belongsToMany(Amenity::class, 'room_type_amenity');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(RoomTypePhoto::class);
    }

    public function bedArrangements(): HasMany
    {
        return $this->hasMany(UnitBedArrangement::class);
    }
}