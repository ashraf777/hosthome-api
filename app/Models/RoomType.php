<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RoomType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hosting_company_id',
        'property_id',
        'name',
        'max_adults',
        'max_children',
        'size',
        'weekday_price',
        'weekend_price',
        'status',
    ];

    /**
     * Get the hosting company that owns the room type.
     */
    public function hostingCompany(): BelongsTo
    {
        return $this->belongsTo(HostingCompany::class);
    }

    public function property(): BelongsTo // <-- CORRECTED
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * The properties that use this room type.
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_room_type');
    }

    /**
     * Get the photos for the room type.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(RoomTypePhoto::class);
    }

    /**
     * Get the amenities for the room type.
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'room_type_amenities');
    }

    /**
     * Get the units for the room type.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
