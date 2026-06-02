<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\Multitenant;

class Task extends Model
{
    use HasFactory, Multitenant;

    protected $fillable = [
        'hosting_company_id',
        'task_name',
        'property_id',
        'preset_task_id',
        'room_type_id',
        'unit_id',
        'status',
        'priority',
        'due_date',
        'cleaning_team_id',
        'checklist_id',
        'num_of_cleaners',
        'host_notes',
        'remarks',
        'created_by_user_id',
        'completed_at',
        // Cleaner app fields
        'blocked_reason',
        'accepted_at',
    ];

    protected $casts = [
        'due_date'    => 'date',
        'completed_at' => 'datetime',
        'accepted_at'  => 'datetime',
    ];

    public function hostingCompany(): BelongsTo
    {
        return $this->belongsTo(HostingCompany::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function presetTask(): BelongsTo
    {
        return $this->belongsTo(PresetTask::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function cleaningTeam(): BelongsTo
    {
        return $this->belongsTo(CleaningTeam::class);
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class);
    }

    public function taskMedia(): HasMany
    {
        return $this->hasMany(TaskMedia::class);
    }
}
