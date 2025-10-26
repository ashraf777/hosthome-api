<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostType extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    protected $table = 'cost_types';

    public function fixedCosts(): HasMany
    {
        return $this->hasMany(UnitFixedCost::class);
    }
}