<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Property extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hosting_company_id',
        'property_owner_id',
        'name',
        'address_line_1',
        'city',
        'state',
        'zip_code',
        'country',
        'timezone',
        'property_type_ref_id',
        'listing_status',
        'status',
        'check_in_time',
        'check_out_time',
        'min_nights',
        'max_nights',
    ];

    /**
     * Get the hosting company that owns the property.
     */
    public function hostingCompany(): BelongsTo
    {
        return $this->belongsTo(HostingCompany::class);
    }

    /**
     * Get the owner of the property.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(PropertyOwner::class, 'property_owner_id');
    }

    /**
     * The room types that belong to the property.
     */
    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(RoomType::class, 'property_room_type');
    }

    /**
     * Get the bookings for the property.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the amenities for the property.
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_amenities');
    }

    /**
     * Get the references for the property.
     */
    public function references(): HasMany
    {
        return $this->hasMany(PropertyReference::class);
    }

    /**
     * Get the property type reference.
     */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyReference::class, 'property_type_ref_id');
    }
}
