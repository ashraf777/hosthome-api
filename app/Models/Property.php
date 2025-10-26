<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Property extends Model
{
    use HasFactory;
    
    // NOTE: This model must use the Tenant's Database Connection dynamically at runtime.

    protected $fillable = [
        'property_owner_id',
        'property_type_ref_id', // Foreign key to PropertyReference
        'name',
        'address_line_1',
        'city',
        'zip_code',
        'timezone',
        'listing_status', // enum: draft, active, archived
        'status', // tinyInteger default 0
    ];

    protected $casts = [
        'status' => 'integer',
        'listing_status' => 'string',
    ];

    public function owner()
    {
        return $this->belongsTo(PropertyOwner::class, 'property_owner_id');
    }
    
    // Relationship for the standardized type (e.g., 'Apartment Complex')
    public function typeReference()
    {
        return $this->belongsTo(PropertyReference::class, 'property_type_ref_id');
    }
    
    public function roomTypes()
    {
        return $this->hasMany(RoomType::class);
    }
}