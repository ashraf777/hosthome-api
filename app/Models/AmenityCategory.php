<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AmenityCategory extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'amenity_categories';

    public function amenities(): HasMany
    {
        return $this->hasMany(Amenity::class, 'amenity_category_id');
    }
}