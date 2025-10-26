<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostingCompany extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'country_id',
        'contact_email',
        'db_host',
        'db_name',
        'db_user',
        'db_pass',
        'plan_id',
        'status',
    ];

    // --- Relationships (All point to Master DB tables) ---

    /**
     * A Hosting Company belongs to a Country (for localization and taxes).
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * A Hosting Company is subscribed to a Plan.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * A Hosting Company has many Users (admins/staff who log into the web app).
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * A Hosting Company has many Subscriptions (history of plans).
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}