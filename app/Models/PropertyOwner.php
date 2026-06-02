<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\Multitenant;

class PropertyOwner extends Model
{
    use HasFactory, Multitenant;

    protected $fillable = [
        'full_name',
        'email',
        'status', // tinyInteger default 0
        'hosting_company_id',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}