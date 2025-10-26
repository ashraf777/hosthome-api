<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'hosting_company_id',
        'plan_id',
        'started_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * A Subscription belongs to one Hosting Company.
     */
    public function hostingCompany()
    {
        return $this->belongsTo(HostingCompany::class);
    }

    /**
     * A Subscription references one Plan.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
