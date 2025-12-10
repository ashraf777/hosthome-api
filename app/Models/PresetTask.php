<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresetTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'hosting_company_id',
        'preset_task_name',
        'property_id',
        'room_type_id',
        'unit_id',
        'trigger_type',
        'cleaning_team_id',
        'num_of_cleaners',
        'checklist_id',
        'remark',
    ];

    public function hostingCompany(): BelongsTo
    {
        return $this->belongsTo(HostingCompany::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
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

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
