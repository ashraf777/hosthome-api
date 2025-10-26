<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price_monthly',
        'features',
        'status',
    ];

    protected $casts = [
        'features' => 'array', // Casts the JSON column to a PHP array
        'price_monthly' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * A Plan can have many Hosting Companies subscribed to it (via Subscriptions).
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
