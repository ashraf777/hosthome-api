<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitFixedCost extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'unit_fixed_costs';

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function costType(): BelongsTo
    {
        return $this->belongsTo(CostType::class);
    }
}