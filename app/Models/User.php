<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\HasPermissions; // CRITICAL: Import the custom trait

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPermissions; // Use the custom trait

    /**
     * Override the can method to match the expected signature.
     */
    public function can($abilities, $arguments = [])
    {
        // If the trait provides can($permissionName), delegate accordingly
        if (method_exists($this, 'canPermission')) {
            return $this->canPermission($abilities, $arguments);
        }
        // Fallback to parent implementation
        return parent::can($abilities, $arguments);
    }

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
        'access_token', // Hide token by default for security
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships (Based on your schema)
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    
    public function hostingCompany()
    {
        return $this->belongsTo(HostingCompany::class);
    }
}