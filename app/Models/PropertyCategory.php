<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyCategory extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    protected $table = 'property_categories';

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'property_category_id');
    }
}