<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyReference extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'key',
        'value',
    ];

    // No relationships needed here as this is a simple lookup table.
}