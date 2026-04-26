<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPermissions;

    protected $fillable = [
        'name',
        'email',
        'password',
        'access_token',
        'status',
        'role_id',
        'hosting_company_id',
    ];

    protected $hidden = [
        'password',
        'access_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
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