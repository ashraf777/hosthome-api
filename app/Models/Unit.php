<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'property_units';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'property_id', // <-- ADDED
        'room_type_id',
        'unit_type_ref_id',
        'unit_identifier',
        'status',
        'description',
        'about',
        'guest_access',
        'owner_user_id',
        'max_free_stay_days',
    ];

    /**
     * Get the property that the unit belongs to.
     */
    public function property(): BelongsTo // <-- ADDED
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the room type that the unit belongs to.
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Get the unit type reference for the unit.
     */
    public function unitTypeRef(): BelongsTo
    {
        return $this->belongsTo(PropertyUnitReference::class, 'unit_type_ref_id');
    }

    /**
     * Get the owner of the unit.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Get the channel mappings for the unit.
     */
    public function channelMappings(): HasMany
    {
        // Assuming a ChannelMapping model exists for the 'channels_mapping' table
        return $this->hasMany(ChannelMapping::class, 'property_unit_id');
    }
}
