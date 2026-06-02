<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Traits\Multitenant;

class RoomType extends Model
{
    use HasFactory, Multitenant;

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
        'room_setup',
    ];

    protected $casts = [
        'room_setup' => 'array',
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
        return $this->belongsTo(Property::class, 'property_id', 'id');
    }

    // The inverse `property()` BelongsTo relation already exists above.
    // Removed properties() belongsToMany because room_types has property_id.

    /**
     * Get the photos for the room type.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class, 'photo_type_id')->where('photo_type', 'room_type'   );
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
