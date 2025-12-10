<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CleaningTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'hosting_company_id',
        'team_leader_id',
        'team_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hostingCompany()
    {
        return $this->belongsTo(HostingCompany::class, 'hosting_company_id');
    }

    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
