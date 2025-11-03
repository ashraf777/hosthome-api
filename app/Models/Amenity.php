<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Amenity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'amenities_reference_id',
        'specific_name',
        'status',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
        'type' => 'integer',
    ];

    /**
     * Get the amenity reference that the amenity belongs to.
     */
    public function amenityReference(): BelongsTo
    {
        return $this->belongsTo(AmenityReference::class, 'amenities_reference_id');
    }
}
