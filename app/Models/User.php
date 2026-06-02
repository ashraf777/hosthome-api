<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\HasPermissions;
use App\Traits\Multitenant;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPermissions, Multitenant;

    protected $fillable = [
        'name',
        'email',
        'password',
        'access_token',
        'status',
        'role_id',
        'hosting_company_id',
        // Cleaner app fields
        'availability_status',
        'fcm_token',
        'login_pin',
        'pin_expires_at',
    ];

    protected $hidden = [
        'password',
        'access_token',
        'login_pin',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'pin_expires_at'    => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hostingCompany()
    {
        return $this->belongsTo(HostingCompany::class);
    }
}